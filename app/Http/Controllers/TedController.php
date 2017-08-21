<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TedController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function search(Request $request){
        $v = app('validator')->make($request->all(), [
            'search' => 'alpha',
            'type' => 'alpha_num|max:1',
            'nature' => 'alpha',
            'lang' => 'alpha',
            'country' => 'alpha',
            'show_expired' => 'boolean',
            "categories" => "",

            'take' => 'numeric|max:50',
            'offset' => 'numeric',
            'order' => 'alpha'
        ]);


        if($v->fails()){
            if($request->isJson())
                return response()->json(['errors' => $v->errors()]);
            else
                return view('master', ['category_list' => json_encode(app('db')->table('categories')->select('code', 'name')->get())]);
        }

        $notices = app('db')->table('notices')->join('notice_details', 'notices.id', '=', 'notice_details.notice_id');

        if($request->has('search')){
            $notices = $notices->whereRaw("MATCH (notice_details.title, notice_details.description) against (? in BOOLEAN MODE)", $request->get('search'));
        }


        if($request->has('type'))
            $notices->where('type', $request->get('type'));

        if($request->has('nature'))
            $notices->where('nature', $request->get('nature'));
        if($request->has('lang'))
            $notices->where('lang', $request->get('lang'));
        if($request->has('country'))
            $notices->where('country', $request->get('country'));
        if($request->has('show_expired'))
            $notices->whereRaw("deadline IS NOT NULL and deadline <= ?", [date('Y-m-d')]);
        if($request->has('categories')){
            {
                $codes = explode(',', $request->get('categories'));
                foreach($codes as $index => $code){
                    $codes[$index] = "^$code";
                }
                $notices->whereIn('notices.id', function($q) use ($codes){
                    $q->select('notices.id')->from('notices')->
                        join('notice_categories', 'notices.id', '=', 'notice_categories.notice_id')->
                        join('categories', 'categories.id', '=', 'notice_categories.category_id')->
                        whereRaw("categories.code REGEXP ?", [implode('|', $codes)]);
                });
            }
        }

//        app('db')->enableQueryLog();
        $total = $notices->count();
//        dd(app('db')->getQueryLog());

        if($request->has('order')){
            if($request->get('order') == 'deadline')
                $notices->orderByRaw('ISNULL(deadline), deadline asc, title asc');
            if($request->get('order') == 'title')
                $notices->orderBy('title', 'asc');
            if($request->get('order') == 'valuehl')
                $notices->orderByRaw('ISNULL(value), -CAST(`value` AS UNSIGNED) DESC')->orderBy('title', 'asc');
            if($request->get('order') == 'valuelh')
                $notices->orderByRaw('ISNULL(value), CAST(`value` AS UNSIGNED) DESC')->orderBy('title', 'asc');
        }
        else
            $notices->orderByRaw('ISNULL(published), published asc, title asc');

        $notices->
        take($request->get('take', 20))->
        skip($request->get('offset', 0));

        if($request->isJson())
            return response()->json(['total' => $total, 'results' => $notices->get()]);
        else
            return view('master', ['total' => $total,
                'category_list' => json_encode(app('db')->table('categories')->select('code', 'name')->get())]);
    }


    /**
     * @param Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function view(Request $request, $slug){

        $notice = app('db')->table('notices')->join('notice_details', 'notices.id', '=', 'notice_details.notice_id')->where('slug', $slug)->first();

        if(!$notice){
            if($request->acceptsJson()){
                return response()->json(['error' => "The requested notice could not be found."], 404);
            }
            else{
                return view('master', [['category_list' => json_encode(app('db')->table('categories')->select('code', 'name')->get())]]);
            }
        }

        $contacts = app('db')->table('notice_contacts')->where('notice_id', $notice->id)->get();
        $lots = app('db')->table('notice_lots')->where('notice_id', $notice->id)->get();
        $awards = app('db')->table('notice_awards')->where('notice_id', $notice->id)->get();
        $categories = app('db')->table('notice_categories')->where('notice_id', $notice->id)->get();

        if($request->acceptsJson()){
            return response()->json(
                [
                    'notice' => $notice,
                    'contacts' => $contacts,
                    'lots' => $lots,
                    'awards' => $awards,
                    'categories' => $categories
                ], 200);
        }
        else{
            return view('master',
                [
                    'notice' => json_encode($notice),
                    'contacts' => json_encode($contacts),
                    'lots' => json_encode($lots),
                    'awards' => json_encode($awards),
                    'categories' => json_encode($categories),
                    'category_list' => json_encode(app('db')->table('categories')->select('code', 'name')->get())
                ]);
        }
    }

    public function total(){
        $total = app('cache')->remember('total', 500, function(){

            return app('db')->table('notices')->count();

        });
        return response()->json(['total' => $total], 200);

    }
}
