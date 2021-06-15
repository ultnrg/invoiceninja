<?php

namespace App\Http\Controllers\ClientPortal;

use App\Events\Quote\QuoteWasApproved;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortal\ProcessQuotesInBulkRequest;
use App\Http\Requests\ClientPortal\ShowQuoteRequest;
use App\Jobs\Invoice\InjectSignature;
use App\Models\Quote;
use App\Utils\Ninja;
use App\Utils\TempFile;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class QuoteController extends Controller
{
    use MakesHash;

    /**
     * Display a listing of the quotes.
     *
     * @return Factory|View
     */
    public function index()
    {
        return $this->render('quotes.index');
    }

    /**
     * Display the specified resource.
     *
     * @param ShowQuoteRequest $request
     * @param Quote $quote
     * @return Factory|View|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function show(ShowQuoteRequest $request, Quote $quote)
    {
        $data = [
            'quote' => $quote,
        ];

        if ($request->query('mode') === 'fullscreen') {
            return render('quotes.show-fullscreen', $data);
        }

        return $this->render('quotes.show', $data);
    }

    public function bulk(ProcessQuotesInBulkRequest $request)
    {
        $transformed_ids = $this->transformKeys($request->quotes);

        if ($request->action == 'download') {
            return $this->downloadQuotePdf((array) $transformed_ids);
        }

        if ($request->action = 'approve') {
            return $this->approve((array) $transformed_ids, $request->has('process'));
        }

        return back();
    }

    protected function downloadQuotePdf(array $ids)
    {
        $quotes = Quote::whereIn('id', $ids)
            ->whereClientId(auth()->user()->client->id)
            ->get();

        if (! $quotes || $quotes->count() == 0) {
            return;
        }

        if ($quotes->count() == 1) {

           $file = $quotes->first()->pdf_file_path();
           return response()->download($file, basename($file), ['Cache-Control:' => 'no-cache'])->deleteFileAfterSend(true);
        }

        // enable output of HTTP headers
        $options = new Archive();
        $options->setSendHttpHeaders(true);

        // create a new zipstream object
        $zip = new ZipStream(date('Y-m-d').'_'.str_replace(' ', '_', trans('texts.invoices')).'.zip', $options);

        foreach ($quotes as $quote) {
            $zip->addFileFromPath(basename($quote->pdf_file_path()), TempFile::path($quote->pdf_file_path()));
        }

        // finish the zip stream
        $zip->finish();
    }

    protected function approve(array $ids, $process = false)
    {
        $quotes = Quote::whereIn('id', $ids)
            ->whereClientId(auth()->user()->client->id)
            ->get();

        if (! $quotes || $quotes->count() == 0) {
            return redirect()->route('client.quotes.index');
        }

        if ($process) {
            foreach ($quotes as $quote) {
                $quote->service()->approve(auth()->user())->save();
                event(new QuoteWasApproved(auth('contact')->user(), $quote, $quote->company, Ninja::eventVars()));

                if (request()->has('signature') && !is_null(request()->signature) && !empty(request()->signature)) {
                    InjectSignature::dispatch($quote, request()->signature);
                }
            }

            return redirect()
                ->route('client.quotes.index')
                ->withSuccess('Quote(s) approved successfully.');
        }

        return $this->render('quotes.approve', [
            'quotes' => $quotes,
        ]);
    }
}
