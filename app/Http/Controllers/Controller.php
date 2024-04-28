<?php

namespace App\Http\Controllers;


use App\Models\BabyName;
use App\Models\PostedName;
use App\Models\Winner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class Controller
{
    public function index()
    {
        $title           = '赤ちゃんの名前をあてよう！';
        $challenge_route = route('challenge');

        return view('index', compact('title', 'challenge_route'));
    }

    public function register()
    {
        $title = '赤ちゃんの名前を登録する！';
        $route = route('store');

        return view('store', compact('title', 'route'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
        ]);

        try {
            DB::beginTransaction();

            BabyName::query()->create([
                'name' => Hash::make($validatedData['name']),
            ]);

            DB::commit();
        } catch (\RuntimeException $e) {
            DB::rollback();

            Log::error($e->getMessage());

            return response()->json([
                'message' => '登録に失敗しました。',
                'success' => false
            ], 422);
        }

        return response()->json([
            'message' => '登録が完了しました。',
            'success' => true
        ]);
    }

    public function challenge(Request $request)
    {
        $validatedData = $request->validate([
            'babyname' => 'required',
            'nickname' => 'nullable',
        ]);

        $success = false;

        try {
            DB::beginTransaction();;

            $babyName = BabyName::query()
                ->orderBy('id', 'DESC')->first();

            PostedName::query()
                ->create([
                    'name'        => $validatedData['babyname'],
                    'nickname'    => $validatedData['nickname'] ?? '名無しさん',
                    'babyname_id' => $babyName->id,
                ]);

            $tryCount = PostedName::query()
                ->where([
                    'nickname'    => $validatedData['nickname'] ?? '名無しさん',
                    'babyname_id' => $babyName->id,
                ])
                ->count();

            if (Hash::check($validatedData['babyname'], $babyName->name)) {
                Winner::query()
                    ->create([
                        'nickname'    => $validatedData['nickname'] ?? '名無しさん',
                        'babyname_id' => $babyName->id,
                        'try_count'   => $tryCount
                    ]);

                $success = true;
            }

            session()->put([
                'babyname' => $validatedData['babyname'],
                'nickname' => $validatedData['nickname'],
                'tryCount' => $tryCount
            ]);

            DB::commit();
        } catch (\RuntimeException $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'  => $success,
            'message'  => $success ? 'おめでとうございます！！大正解です！' : '不正解です！',
            'tryCount' => $tryCount
        ]);
    }
}
