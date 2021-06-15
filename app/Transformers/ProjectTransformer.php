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

namespace App\Transformers;

use App\Models\Document;
use App\Models\Project;
use App\Utils\Traits\MakesHash;

/**
 * class ProjectTransformer.
 */
class ProjectTransformer extends EntityTransformer
{
    use MakesHash;

    protected $defaultIncludes = [
        'documents',
    ];

    /**
     * @var array
     */
    protected $availableIncludes = [
        'documents'
    ];

    public function includeDocuments(Project $project)
    {
        $transformer = new DocumentTransformer($this->serializer);

        return $this->includeCollection($project->documents, $transformer, Document::class);
    }

    public function transform(Project $project)
    {
        return [
            'id' => (string) $this->encodePrimaryKey($project->id),
            'user_id' => (string) $this->encodePrimaryKey($project->user_id),
            'assigned_user_id' => (string) $this->encodePrimaryKey($project->assigned_user_id),
            'client_id' => (string) $this->encodePrimaryKey($project->client_id),
            'name' => $project->name ?: '',
            'number' => $project->number ?: '',
            'created_at' => (int) $project->created_at,
            'updated_at' => (int) $project->updated_at,
            'archived_at' => (int) $project->deleted_at,
            'is_deleted' => (bool) $project->is_deleted,
            'task_rate' => (float) $project->task_rate,
            'due_date' => $project->due_date ?: '',
            'private_notes' => (string) $project->private_notes ?: '',
            'public_notes' => (string) $project->public_notes ?: '',
            'budgeted_hours' => (float) $project->budgeted_hours,
            'custom_value1' => (string) $project->custom_value1 ?: '',
            'custom_value2' => (string) $project->custom_value2 ?: '',
            'custom_value3' => (string) $project->custom_value3 ?: '',
            'custom_value4' => (string) $project->custom_value4 ?: '',
            'color' => (string) $project->color ?: '',
        ];
    }
}
