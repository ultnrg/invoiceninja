<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */
namespace Tests\Feature;

use App\Utils\Traits\MakesHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 * @test
 * @covers App\Http\Controllers\VendorController
 */
class VendorApiTest extends TestCase
{
    use MakesHash;
    use DatabaseTransactions;
    use MockAccountData;

    public function setUp() :void
    {
        parent::setUp();

        $this->makeTestData();

        Session::start();

        $this->faker = \Faker\Factory::create();

        Model::reguard();
    }

    public function testVendorPost()
    {
        $data = [
            'name' => $this->faker->firstName,
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/vendors', $data);

        $response->assertStatus(200);
    }

    public function testVendorPut()
    {
        $data = [
            'name' => $this->faker->firstName,
            'id_number' => 'Coolio',
            'number' => 'wiggles'
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->put('/api/v1/vendors/'.$this->encodePrimaryKey($this->vendor->id), $data);

        $response->assertStatus(200);

        $arr = $response->json();

        $this->assertEquals('Coolio', $arr['data']['id_number']);
        $this->assertEquals('wiggles', $arr['data']['number']);

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->put('/api/v1/vendors/'.$this->encodePrimaryKey($this->vendor->id), $data);

        $response->assertStatus(200);


        try{
            $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/vendors/', $data);


        }catch(ValidationException $e){

            $response->assertStatus(302);

        }

    }

    public function testVendorGet()
    {
        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->get('/api/v1/vendors/'.$this->encodePrimaryKey($this->vendor->id));

        $response->assertStatus(200);
    }

    public function testVendorNotArchived()
    {
        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->get('/api/v1/vendors/'.$this->encodePrimaryKey($this->vendor->id));

        $arr = $response->json();

        $this->assertEquals(0, $arr['data']['archived_at']);
    }

    public function testVendorArchived()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->vendor->id)],
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/vendors/bulk?action=archive', $data);

        $arr = $response->json();

        $this->assertNotNull($arr['data'][0]['archived_at']);
    }

    public function testVendorRestored()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->vendor->id)],
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/vendors/bulk?action=restore', $data);

        $arr = $response->json();

        $this->assertEquals(0, $arr['data'][0]['archived_at']);
    }

    public function testVendorDeleted()
    {
        $data = [
            'ids' => [$this->encodePrimaryKey($this->vendor->id)],
        ];

        $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->post('/api/v1/vendors/bulk?action=delete', $data);

        $arr = $response->json();

        $this->assertTrue($arr['data'][0]['is_deleted']);
    }
}
