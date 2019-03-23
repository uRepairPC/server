<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\traits\ImageTrait;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ImageTrait;

    /** @var string */
    private $_model = User::class;

    /** @var int */
    private const RANDOM_PASSWORD_LEN = 10;

    public function __construct()
    {
        $this->allowRoles([
            User::ROLE_WORKER => [
                'index', 'show', 'update', 'updatePassword', 'updateEmail', 'getImage', 'updateImage', 'destroyImage',
            ],
            User::ROLE_USER => [
                'index', 'show', 'update', 'updatePassword', 'updateEmail', 'getImage', 'updateImage', 'destroyImage',
            ],
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string',
            'columns.*' => 'string|in:' . join(',', User::ALLOW_COLUMNS_SEARCH),
            'sortColumn' => 'string|in:' . join(',', User::ALLOW_COLUMNS_SORT),
            'sortOrder' => 'string|in:ascending,descending',
        ]);

        $query = User::query();

        // Search
        if ($request->has('search') && $request->has('columns') && count($request->columns)) {
            foreach ($request->columns as $column) {
                $query->orWhere($column, 'LIKE', '%' . $request->search . '%');
            }
        }

        // Order
        if ($request->has('sortColumn')) {
            $query->orderBy($request->sortColumn, $request->sortOrder === 'descending' ? 'desc' : 'asc');
        }

        $list = $query->paginate(self::PAGINATE_DEFAULT);

        return response()->json($list);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  UserRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserRequest $request)
    {
        $password = str_random(self::RANDOM_PASSWORD_LEN);

        $user = new User;
        $user->email = $request->email;
        $user->first_name = $request->first_name;
        $user->middle_name = $request->middle_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->description = $request->description;
        $this->setRole($user, $request->role);
        $user->password = bcrypt($password);

        if (! $user->save()) {
            return response()->json(['message' => 'Виникла помилка при збереженні'], 422);
        }

//        TODO Send email with password

        return response()->json(['message' => 'Збережено', 'user' => $user]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $user = User::findOrFail($id);

        return response()->json(['message' => 'Користувач отриман', 'user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UserRequest  $request
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request, int $id)
    {
        $me = Auth::user();

        if (! $me->admin() && $me->id !== $id) {
            return response()->json(['message' => 'Немає прав'], 403);
        }

        $user = User::findOrFail($id);
        $user->first_name = $request->has('first_name') ? $request->first_name : $user->first_name;
        $user->middle_name = $request->has('middle_name') ? $request->middle_name : $user->middle_name;
        $user->last_name = $request->has('last_name') ? $request->last_name : $user->last_name;
        $user->phone = $request->has('phone') ? $request->phone : $user->phone;
        $user->description = $request->has('description') ? $request->description : $user->description;

        if ($request->has('role')) {
            $this->setRole($user, $request->role);
        }

        if (! $user->save()) {
            return response()->json(['message' => 'Виникла помилка при збереженні'], 422);
        }

        return response()->json(['message' => 'Збережено', 'user' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $me = Auth::user();

        if ($me->id === $id) {
            return response()->json(['message' => 'Неможливо видалити самого себе'], 403);
        }

        if (User::destroy($id)) {
            return response()->json(['message' => 'Користувач видалений']);
        }

        return response()->json(['message' => 'Виникла помилка при видаленні'], 422);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEmail(Request $request, int $id)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        $me = Auth::user();

        if (! $me->admin() && $me->id !== $id) {
            return response()->json(['message' => 'Немає прав'], 403);
        }

        $user = User::findOrFail($id);
        $user->email = $request->email;

        if (! $user->save()) {
            return response()->json(['message' => 'Виникла помилка при збереженні'], 422);
        }

//        TODO Send message to email

        return response()->json(['message' => 'Збережено', 'user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateImage(Request $request, int $id)
    {
        $me = Auth::user();

        if (! $me->admin() && $me->id !== $id) {
            return response()->json(['message' => 'Немає прав'], 403);
        }

        return $this->setImage($request, $id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyImage(int $id)
    {
        $me = Auth::user();

        if (! $me->admin() && $me->id !== $id) {
            return response()->json(['message' => 'Немає прав'], 403);
        }

        return $this->deleteImage($id);
    }

    /**
     * Update the specified resource in storage.
     * If the user edit the same, need password
     * Another - send email to the user with
     * new random password.
     *
     * @param   Request  $request
     * @param   int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request, int $id)
    {
        $me = Auth::user();

        if ($me->id === $id) {
            $request->validate([
                'password' => 'required|string',
            ]);
        }

        if (! $me->admin() && $me->id !== $id) {
            return response()->json(['message' => 'Немає прав'], 403);
        }

        $user = User::findOrFail($id);
        $password = str_random(self::RANDOM_PASSWORD_LEN);

        // Edit another user => send email with a new random password
        if ($me->id !== $id) {
            $user->password = bcrypt($password);

            if (! $user->save()) {
                return response()->json(['message' => 'Виникла помилка при збереженні'], 422);
            }

            // TODO Send email with new password to the user

            return response()->json(['message' => 'Пароль змінений та відправлений на почту']);
        }

        $user->password = bcrypt($request->password);

        if (! $user->save()) {
            return response()->json(['message' => 'Виникла помилка при збереженні'], 422);
        }

        return response()->json(['message' => 'Пароль змінений']);
    }

    /**
     * @param  User  $user
     * @param  String  $role
     * @return bool
     */
    private function setRole(User &$user, string $role)
    {
        $me = Auth::user();

        if (empty($role)) {
            return false;
        }

        // No admin can't set a role
        if (! $me->admin()) {
            return false;
        }

        // Block change myself a role
        if ($me->id === $user->id) {
            return false;
        }

        $user->role = $role;
        return true;
    }
}