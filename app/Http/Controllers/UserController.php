<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin', ['except' => ['updateUserName']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return Collection
     */
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'limit' => 'nullable|int|max:50',
            'offset' => 'nullable|int',
        ]);

        return $products = User::query()
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Collection|null
     */
    public function show($id)
    {
        return User::query()->find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Collection
     * @throws \Throwable
     */
    public function update(Request $request, $id)
    {
        $inputs = $request->validate([
            'email' => 'nullable|unique:users',
            'role' => 'nullable|exists:roles,id',
            'email_verified_at' => 'nullable|date',
            'attach_role' => 'required_with:role|boolean',
        ]);

        $user = User::query()->findOrFail($id);

        if (array_key_exists('role', $inputs)) {
            if (!is_admin(Auth::user())) {
                abort(403, 'دسترسی شما به این بخش امکان پذیر نمی باشد.');
            }

            if ($inputs['attach_role']) {
                $user->roles()->sync($inputs['role']);
            } else {
                $user->roles()->detach($inputs['role']);
            }

            unset($inputs['role']);
            unset($inputs['attach_role']);
        }

        foreach ($inputs as $key => $value) {
            $user->$key = $value;
        }

        $user->saveOrFail();

        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return string
     * @throws \Exception
     */
    public function destroy($id)
    {
        if (!is_admin(Auth::user())) {
            abort(403, 'دسترسی شما به این بخش امکان پذیر نمی باشد.');
        }

        $user = User::query()->findOrFail($id);

        $user->delete();

        return __('messages.user_is_deleted', ['name' => $user->name]);
    }

    /**
     * @param Request $request
     * @return User
     * @throws \Throwable
     */
    public function updateUserName(Request $request)
    {
        $inputs = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->name = $inputs['name'];

        $user->saveOrFail();

        return $user;
    }
}
