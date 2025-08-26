<?php

namespace App\Traits;


use Carbon\Carbon;
use Illuminate\Http\Request;

trait QueryParams
{
    public function commonQuery($query, Request $request)
    {
        $search     = $request->get('search');
        $dateSearch = $request->get('dateSearch');
        $isChecked  = $request->get('isChecked', false);

        if ($isChecked) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        if (!blank($search)) {
            $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%");
        }
        if (!blank($dateSearch)) {
            $start = Carbon::parse($dateSearch[0])->startOfDay()->toDateTimeString();
            $end   = Carbon::parse($dateSearch[1])->endOfDay()->toDateTimeString();
            $query->whereBetween('created_at', [$start, $end]);
        }
        return $query;
    }


    public function commonQueryWithoutTrash($query, Request $request)
    {
        $search = $request->get('search');

        if (!blank($search)) {
            $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%");
        }

        return $query;
    }

    public function commonQueryWithoutSlug($query, Request $request)
    {
        $search = $request->get('search');

        if (!blank($search)) {
            $query->where('title', 'LIKE', "%{$search}%");
        }
        return $query;
    }


    public function commonQueryWithName($query, Request $request)
    {
        $search     = $request->get('search');
        $dateSearch = $request->get('dateSearch');

        if (!blank($search)) {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        if (!blank($dateSearch)) {
            $start = Carbon::parse($dateSearch[0])->startOfDay()->toDateTimeString();
            $end   = Carbon::parse($dateSearch[1])->endOfDay()->toDateTimeString();
            $query->whereBetween('created_at', [$start, $end]);
        }
        return $query;
    }


    public function productQuery($query, Request $request)
    {
        $search    = $request->get('search');
        $category  = $request->get('category');
        $isChecked = $request->get('isChecked', false);

        if ($isChecked) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        if (!blank($search)) {
            $query->where('title_en', 'LIKE', "%{$search}%")
                ->orWhere('slug', 'LIKE', "%{$search}%");
        }
        if (!blank($category)) {
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('id', $category);
            });
        }
        return $query;
    }

    public function commonQueryWithDisplayName($query, Request $request)
    {
        $search = $request->get('search');

        if (!blank($search)) {
            $query->where('display_name', 'LIKE', "%{$search}%");
        }
        return $query;
    }

    public function commonQueryWithNameOrPhone($query, Request $request)
    {
        $search = $request->get('search');

        if (!blank($search)) {
            $query
                ->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
        }

        return $query;
    }


}
