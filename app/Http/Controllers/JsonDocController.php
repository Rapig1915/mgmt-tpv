<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\JsonDocument;

class JsonDocController extends Controller
{
    public $ignoredTypes = [
        'site-errors',
        'tpv-api',
        'live-agent-stats',
        'stats-job-2',
    ];

    public static function routes()
    {
        Route::group(
            ['middleware' => ['auth']],
            function () {
                Route::get('json/docs', 'JsonDocController@json_documents')->name('json_documents.docs');
                Route::get('json/docs/list', 'JsonDocController@list')->name('json_documents.list');
            }
        );
    }

    public function json_documents()
    {
        $selectedType = request()->input('type');
        if (empty($selectedType)) {
            $selectedType = '';
        }
        return view('generic-vue')->with(
            [
                'componentName' => 'json-documents',
                'title' => 'JSON Documents',
                'parameters' => [
                    'types' => $this->getDocumentTypes(),
                    'selected-type' => json_encode($selectedType),
                ]
            ]
        );
    }

    public function getDocumentTypes()
    {
        return DB::table('json_documents')
            ->whereNotIn('document_type', $this->ignoredTypes)
            ->select('document_type')
            ->groupBy('document_type')
            ->get()
            ->pluck('document_type')
            ->map(function ($item) {
                return ['title' => str_replace('-', ' ', str_replace('_', ' ', Str::title($item))), 'value' => $item];
            })->toJson();
    }

    public function list(Request $request)
    {
        $selectedType = $request->input('type');
        $searchV = $request->input('search');

        $q = JsonDocument::whereNotIn(
            'document_type',
            $this->ignoredTypes
        );

        if (!empty($selectedType)) {
            $q = $q->where('document_type', $selectedType);
        }

        if (!empty($searchV)) {
            $q = $q->where(function ($query) use ($searchV) {
                $query->where('ref_id', $searchV)
                    ->orWhere('document', 'like', '%' . $searchV . '%');
            });
        }

        return $q->orderBy(
            'created_at',
            'desc'
        )->paginate(30);
    }
}
