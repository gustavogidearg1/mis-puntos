<?php

namespace App\Http\Controllers\Abm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
    $this->middleware(['auth']);
    $this->middleware(['role:admin_sitio|admin_empresa']);
    }

    public function index(Request $request)
{
    $q         = $request->string('q')->toString();
    $companyId = $request->input('company_id');
    $roleName  = $request->input('role');

    // Para los filtros del select
    $companies = Company::query()->orderBy('name')->get();
    $roles     = Role::query()->orderBy('name')->get();

    $users = User::query()
        ->with(['roles', 'company'])
        ->when($q, function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        })
        ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
        ->when($roleName, function ($qq) use ($roleName) {
            $qq->whereHas('roles', fn ($r) => $r->where('name', $roleName));
        })
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    return view('abm.users.index', compact('users', 'q', 'companies', 'roles', 'companyId', 'roleName'));
}


public function create()
{
    $roles = Role::query()->orderBy('name')->pluck('name'); // <- nombres (strings)
    $companies = Company::query()->orderBy('name')->get();

    $paises = \App\Models\Pais::query()->orderBy('nombre')->get();
    $provincias = \App\Models\Provincia::query()->orderBy('nombre')->get();
    $localidades = \App\Models\Localidad::query()->orderBy('nombre')->get();

    return view('abm.users.create', compact('roles','companies','paises','provincias','localidades'));
}


    public function store(Request $request)
{
    $data = $request->validate([
        'name'       => ['required','string','max:120'],
        'email'      => ['required','email','max:180','unique:users,email'],
        'password'   => ['required','string','min:6','max:255','confirmed'],

        'cuil'       => ['nullable','string','max:13'],
        'direccion'  => ['nullable','string','max:255'],

        'company_id'    => ['nullable','integer','exists:companies,id'],
        'pais_id'       => ['nullable','integer','exists:paises,id'],
        'provincia_id'  => ['nullable','integer','exists:provincias,id'],
        'localidad_id'  => ['nullable','integer','exists:localidades,id'],

        'fecha_nacimiento' => ['nullable','date'],
        'activo'           => ['nullable','boolean'],

        'imagen' => ['nullable','image','max:2048'],

        'roles'   => ['nullable','array'],
        'roles.*' => ['string','exists:roles,name'],
    ]);

    if ($request->hasFile('imagen')) {
        $data['imagen'] = $request->file('imagen')->store('users', 'public');
    }

    $user = User::create([
        'name'            => $data['name'],
        'email'           => $data['email'],
        'password'        => Hash::make($data['password']),
        'cuil'            => $data['cuil'] ?? null,
        'direccion'       => $data['direccion'] ?? null,
        'company_id'      => $data['company_id'] ?? null,
        'pais_id'         => $data['pais_id'] ?? null,
        'provincia_id'    => $data['provincia_id'] ?? null,
        'localidad_id'    => $data['localidad_id'] ?? null,
        'fecha_nacimiento'=> $data['fecha_nacimiento'] ?? null,
        'activo'          => isset($data['activo']) ? (bool)$data['activo'] : true,
        'imagen'          => $data['imagen'] ?? null,
    ]);

    $user->syncRoles($data['roles'] ?? []);

    return redirect()->route('abm.users.index')->with('success', 'User created successfully.');
}


    public function show(User $user)
    {
        $user->load(['roles', 'company', 'pais', 'provincia', 'localidad']);

        return view('abm.users.show', compact('user'));
    }

public function edit(User $user)
{
    $companies = Company::query()->orderBy('name')->get();
    $paises = \App\Models\Pais::query()->orderBy('nombre')->get();
    $provincias = \App\Models\Provincia::query()->orderBy('nombre')->get();
    $localidades = \App\Models\Localidad::query()->orderBy('nombre')->get();

    $roles = Role::query()->orderBy('name')->pluck('name'); // strings
    $currentRoleNames = $user->roles->pluck('name')->all();

    return view('abm.users.edit', compact(
        'user','companies','paises','provincias','localidades','roles','currentRoleNames'
    ));
}


    public function update(Request $request, User $user)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
        'password' => ['nullable', 'string', 'min:6', 'max:255'],

        'cuil' => ['nullable', 'string', 'max:13'],
        'direccion' => ['nullable', 'string', 'max:255'],

        'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        'pais_id' => ['nullable', 'integer', 'exists:paises,id'],
        'provincia_id' => ['nullable', 'integer', 'exists:provincias,id'],
        'localidad_id' => ['nullable', 'integer', 'exists:localidades,id'],

        'fecha_nacimiento' => ['nullable', 'date'],
        'activo' => ['nullable', 'boolean'],

        'imagen' => ['nullable', 'image', 'max:2048'],

        'roles' => ['nullable', 'array'],
        'roles.*' => ['string', 'exists:roles,name'],
    ]);

    $updateData = [
        'name' => $data['name'],
        'email' => $data['email'],
        'cuil' => $data['cuil'] ?? null,
        'direccion' => $data['direccion'] ?? null,
        'company_id' => $data['company_id'] ?? null,
        'pais_id' => $data['pais_id'] ?? null,
        'provincia_id' => $data['provincia_id'] ?? null,
        'localidad_id' => $data['localidad_id'] ?? null,
        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
        // OJO: si no mandás el checkbox, viene null. Si querés que “no enviado” no cambie, avisame.
        'activo' => (bool)($data['activo'] ?? false),
    ];

    if ($request->hasFile('imagen')) {
        if ($user->imagen) {
            Storage::disk('public')->delete($user->imagen);
        }
        $updateData['imagen'] = $request->file('imagen')->store('users', 'public');
    }

    if (!empty($data['password'])) {
        $updateData['password'] = Hash::make($data['password']);
    }

    $user->update($updateData);
    $user->syncRoles($data['roles'] ?? []);

    return redirect()->route('abm.users.show', $user)->with('success', 'User updated successfully.');
}



}
