<?php

namespace App\Traits;

trait CSVResponseTrait
{
    private function csv_response(array $list, string $filename, array $column_headers = null)
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename . '.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        if (isset($list) && isset($list[0])) {
            $column_headers = ($column_headers) ? $column_headers : array_keys($list[0]);
            array_unshift($list, $column_headers);

            $callback = function () use ($list) {
                $FH = fopen('php://output', 'w');
                foreach ($list as $row) {
                    fputcsv($FH, $row);
                }
                fclose($FH);
            };

            return response()->stream($callback, 200, $headers);
        } else {
            return response()->json(
                [
                    'message' => 'No results to return.',
                ]
            );
        }
    }

    private function new_csv_response($query, $filename)
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename . '.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        if (isset($query) && $query !== null) {
            $callback = function () use ($query, $filename) {
                try {
                    $FH = fopen('php://output', 'w');
                    $this->headerSent = false;
                    $query->chunk(10, function ($items) use ($FH, $filename) {
                        if (!$this->headerSent) {
                            $first = $items->first();
                            if ($first) {
                                fputcsv($FH, array_keys($first->toArray()));
                                $this->headerSent = true;
                            } else {
                                fwrite($FH, 'There were no records returned.');

                                return;
                            }
                        }

                        $items->values()->each(function ($item) use ($FH) {
                            fputcsv($FH, $item->toArray());
                        });
                    });
                } catch (\Exception $e) {
                    info('Exception in csv response', [$e]);
                } finally {
                    fclose($FH);
                }
            };
            //$callback();
            return response()->stream($callback, 200, $headers);
        }
    }
}
