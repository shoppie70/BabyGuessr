@extends('layout')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-pink-50 p-8">
        <div class="max-w-xl mx-auto shadow p-8 bg-white">
            <form class="api_form" action="{{ $challenge_route }}">
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold leading-7 text-gray-900">
                            {{ $title }}
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600">
                            性別も秘密です。名前を当てられるまで寝られません！
                        </p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                            <div class="col-span-12">
                                <label for="nickname" class="block text-sm font-medium leading-6 text-gray-900">
                                    あなたのニックネーム
                                </label>
                                <div class="mt-2">
                                    <div
                                            class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 w-full">

                                        <input type="text" name="nickname" id="nickname" autocomplete="nickname"
                                               required
                                               class="block flex-1 border-0 bg-transparent py-1.5 px-2 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                                               placeholder="ご両親が確認するためにつかうよ"
                                               value="{{session()->get('nickname')}}"
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-12">
                                <label for="babyname" class="block text-sm font-medium leading-6 text-gray-900">
                                    なまえ
                                </label>
                                <div class="mt-2">
                                    <div
                                            class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-600 sm:max-w-md">
                                        <span
                                                class="flex select-none items-center pl-3 text-gray-500 sm:text-sm">草加 / </span>
                                        <input type="text" name="babyname" id="babyname" autocomplete="babyname"
                                               required
                                               class="block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                                               placeholder=""
                                               value="{{session()->get('babyname')}}"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="">
                        <div class="mb-4 flex items-center justify-end text-sm">
                            <p class="ml-2">
                                <span id="tryCount">{{ session()->get('tryCount') ?? 0 }}</span>回連続チャレンジ中！
                            </p>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="submit"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                送信する
                            </button>
                        </div>
                    </div>
            </form>
        </div>
    </div>
@endsection
