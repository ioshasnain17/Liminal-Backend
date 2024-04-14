<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpParser\Node\Scalar\String_;
use Validator;
use App\Models\Search;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
class SearchController extends Controller
{
    public function saveSearch(Request $request){

        $validator = Validator::make($request->all(), [
            'search_title' => 'required',
            'search_type' => 'required',
            'search_mode' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" =>$validator->errors()->all(), 422]);
        }
            $user = User::where('id', auth()->id())->with(['searches', 'subscription' => function ($q) {
                $q->latest()->first();
            }])->first();
            if ($request->search_type == 'audio') {
                $monthlyLimit = $user->subscription->plan->monthly_audio_tokens;
                $used_token = $user->searches()->whereBetween('created_at',[$user->subscription->start_date, $user->subscription->end_date])->sum('audio_tokens_used');

            }
            if ($request->search_type == 'text') {
                $monthlyLimit = $user->subscription->plan->monthly_text_tokens;
                $used_token = $user->searches()->whereDate('created_at',[$user->subscription->start_date, $user->subscription->end_date])->sum('text_tokens_used');

            }

            if ($user->subscription->end_date >= Carbon::now()) {
                // Check if the user has enough tokens for the search
                if ($used_token >= $monthlyLimit && $user->subscription->plan->id!==1) {
                    return response()->json(['error' => 'Daily token limit reached'], 403);
                }
                   if($request->chat_id==null){
                       
                       $chat_id = Chat::create(['title'=>$request->search_title,'user_id'=>auth()->id()])->id;
                    }
                    else{
                         $chat = Chat::where('id',$request->chat_id)->select('id')->first();
                         $chat_id = $chat->id;
                    }
                if ($request->search_type == 'audio') {
                 
                    $title ="audio";
                    try {
                        if($request->search_mode=="dialog") {
                            $response = Http::attach(
                                'audio_base64',
                                $request->search_title

                            )->post('https://liminal-api-test-hfxs2ewq7a-uc.a.run.app/audio/', [
                                "user_id" => (string)auth()->id(),
                                "session_id" => "1212",
                                "subscription_id" => $user->subscription->plan_id
                            ]);
                        }
                        if($request->search_mode=="perspective") {
                            $response = Http::attach(
                                'audio_base64',
                                $request->search_title

                            )->post('https://prespectives-api-test-hfxs2ewq7a-uc.a.run.app/audio/', [
                                "user_id" => (string)auth()->id(),
                                "user_id" => 7,
                                "session_id" => "1212",
                                "subscription_id" => $user->subscription->plan_id
                            ]);
                        }
                        Search::create([
                            'user_id' => $user->id,
                            'search_date' => now(),
                            'audio_tokens_used' => $request->token,
                        ]);

                    } catch (Exception $e) {
                        return $e->getMessage();
                    }
                    $response = $response->getBody()->getContents();

                }
                if ($request->search_type == 'text') {
                    $request->validate([
                        'search_title' => 'required',
                    ]);
                    $title = $request->search_title;

                    Search::create([
                        'user_id' => $user->id,
                        'search_date' => now(),
                        'text_tokens_used' => strlen($title),
                    ]);
                    if($request->search_mode=="dialog") {
                        $response =
                            Http::post('https://liminal-api-test-hfxs2ewq7a-uc.a.run.app/text/', [
                                "user_input" => (string)$request->search_title,
                                "user_id" => (string)auth()->id(),
                                "session_id" => (string)$chat_id,
                                "subscription_id" => $user->subscription->plan_id

                            ]);
                    }
                    if($request->search_mode=="perspective") {
                        $response =
                            Http::post('https://prespectives-api-test-hfxs2ewq7a-uc.a.run.app/text/', [
                                "user_input" => (string)$request->search_title,
                                "user_id" => (string)auth()->id(),
                                "session_id" => (string)$chat_id,
                                "subscription_id" => $user->subscription->plan_id

                            ]);
                    }
                    $response = $response->getBody()->getContents();
                }
             
               
                    $input = $request->all();
                    $input['chat_id'] = $chat_id;
                    $input['response'] =$response;
                    $input['search_title'] =$title;
                    SearchHistory::create($input);
                
                $response =[
                    'status' =>true,
                    'chat_id' =>$chat_id,
                    'response'=> $response,
                    'message' => "Search Message saved Successfully"
                ];
                return response()->json($response);
            }

            else {
                return response()->json(['message' => 'Plan Expired']);
            }

    }
    public function getHistory($chat_id){
        $data = SearchHistory::where('chat_id',$chat_id)->get();
        $response =[
            'data'=>$data,
            'status' =>true,
            'message' => "data get successfully"
        ];
        return response()->json($response);
    }


    public function delete($id){
        $search = Chat::find($id);
        if($search==null){
            $response =[
                'status' =>false,
                'message' => "Search Data no found"
            ];
        }else{
            $search->delete();
            $response =[
                'status' =>true,
                'message' => "Search deleted successfully"
            ];
        }
        return response()->json($response);
    }
//    public function search(Request $request)
//    {
//
//        $user = User::where('id',auth()->id())->with(['searches','subscription'=>function($q){
//            $q->latest()->first();
//        }])->first();
//        if($request->search_type=='audio') {
//            $dailyLimit = $user->subscription->plan->daily_audio_tokens;
//        }
//        if($request->search_type=='text') {
//            $dailyLimit = $user->subscription->plan->daily_text_tokens;
//        }
//        if($request->search_type=='audio'){
//            $used_token =  $user->searches()->whereDate('created_at', now())->sum('audio_tokens_used');
//        }
//        if($request->search_type=='text'){
//            $used_token =  $user->searches()->whereDate('created_at', now())->sum('text_tokens_used');
//        }
//       if($user->subscription->end_date>=Carbon::now()) {
//           // Check if the user has enough tokens for the search
//           if ($used_token >= $dailyLimit) {
//               return response()->json(['error' => 'Daily token limit reached'], 403);
//           }
//
//           if($request->search_type=='audio') {
//               Search::create([
//                   'user_id' => $user->id,
//                   'search_date' => now(),
//                   'audio_tokens_used' => $request->token,
//               ]);
//           }
//           if($request->search_type=='text') {
//               Search::create([
//                   'user_id' => $user->id,
//                   'search_date' => now(),
//                   'text_tokens_used' => $request->token,
//               ]);
//           }
//           // Log the search
//
//
//           return response()->json(['message' => 'Search successful']);
//       }else{
//           return response()->json(['message' => 'Plan Expired']);
//       }
//    }


}
