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
                return view('master', ['category_list' => $this->categories()]);
        }

        $notices = app('db')->
            table('notices')->
            select(
                'notices.slug',
                'notices.published',
                'notices.deadline',
                'notices.value',
                'notices.currency',
                'notice_details.title'
        );

        $should_join = true;

        if($request->has('search')){
            $should_join = false;
            $notices = $notices->
                join('notice_details', 'notice_details.notice_id', '=', 'notices.id')->
                whereRaw("MATCH (notice_details.title, notice_details.description) against (? in BOOLEAN MODE)", $request->get('search'));
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

                    $notices->
                        join('notice_categories', 'notices.id', '=', 'notice_categories.notice_id')->
                        join('categories', 'categories.id', '=', 'notice_categories.category_id')->
                        whereRaw("categories.code REGEXP ?", [implode('|', $codes)]);
            }
        }

//        app('db')->enableQueryLog();
        $total = $notices->count();
//        dd(app('db')->getQueryLog());

        if($request->has('order')){
            if($request->get('order') == 'deadline')
                $notices->orderByRaw('ISNULL(notices.deadline), notices.deadline asc, notices.slug asc');
            if($request->get('order') == 'title')
                $notices->orderBy('notices.slug', 'asc');
            if($request->get('order') == 'valuehl')
                $notices->orderByRaw('ISNULL(value), -CAST(`value` AS UNSIGNED) DESC')->orderBy('notices.slug', 'asc');
            if($request->get('order') == 'valuelh')
                $notices->orderByRaw('ISNULL(value), CAST(`value` AS UNSIGNED) DESC')->orderBy('notices.slug', 'asc');
        }
        else
            $notices->orderByRaw('notices.published asc, notices.slug asc');

        $notices->
        take($request->get('take', 20))->
        skip($request->get('offset', 0));


        if($should_join){
            $notices->join('notice_details', 'notice_details.notice_id', '=', 'notices.id');
        }

        if($request->isJson())
            return response()->json(['total' => $total, 'results' => $notices->get()]);
        else
            return view('master', [
                'total' => $total,
                'results' => $notices->get(),
                'category_list' => $this->categories()
            ]);
    }


    /**
     * @param Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function view(Request $request, $slug){

        $notice = app('db')->table('notices')->join('notice_details', 'notices.id', '=', 'notice_details.notice_id')->where('slug', $slug)->first();

        if(!$notice){
            if($request->ajax()){
                return response()->json(['error' => "The requested notice could not be found."], 404);
            }
            else{
                return view('master', [['category_list' => $this->categories()]]);
            }
        }

        $contacts = app('db')->table('notice_contacts')->where('notice_id', $notice->id)->get();
        $lots = app('db')->table('notice_lots')->where('notice_id', $notice->id)->get();
        $awards = app('db')->table('notice_awards')->where('notice_id', $notice->id)->get();
        $categories = app('db')->table('notice_categories')->where('notice_id', $notice->id)->get();

        if($request->ajax()){
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
                    'category_list' => $this->categories()
                ]);
        }
    }

    public function home(){
        return view('master', [
            'category_list' => $this->categories(),
            'total' => $this->total()
        ]);
    }

    public function about(){
        return view('master', [
            'category_list' => $this->categories(),
            'total' => $this->total()
        ]);
    }

    public function sitemap(){
        return view('master', [
            'category_list' => $this->categories(),
            'total' => $this->total()
        ]);
    }

    public function disclaimer(){
        return view('master', [
            'category_list' => $this->categories(),
            'total' => $this->total()
        ]);
    }

    public function total(){
        return json_encode(app('cache')->remember('total', 60, function(){
            return app('db')->table('notices')->count();
        }));

    }

    public function categories(){
        return app('files')->get(storage_path('categories_tree.json'));
    }
}
