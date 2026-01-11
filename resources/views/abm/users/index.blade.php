@extends('layouts.app')

@section('title','Users')

@section('content')

<style>
  .mat-sort{display:inline-flex;align-items:center;gap:.25rem;cursor:pointer;text-decoration:none;}
  .mat-sort .sort-icon{font-size:.9rem;opacity:.6;}
  .mat-sort.active{font-weight:600;}
</style>

<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-people"></i> Users</h3>

    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.users.create') }}" class="btn btn-primary btn-mat btn-sm">
        <i class="bi bi-plus-circle"></i> New
      </a>
    </div>
  </div>

  <div class="card-body">

    {{-- Filters --}}
    <form method="GET" class="row g-2 align-items-end mb-3" action="{{ route('abm.users.index') }}">
      <div class="col-12 col-md-3">
        <label class="form-label">Buscar</label>
        <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="name, email, cuil...">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Company</label>
        <select name="company_id" class="form-select">
          <option value="">—</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected((string)request('company_id')===(string)$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <option value="">—</option>
          @foreach($roles as $r)
            <option value="{{ $r->name }}" @selected(request('role')===$r->name)>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label">Active</label>
        <select name="activo" class="form-select">
          <option value="">—</option>
          <option value="1" @selected(request('activo')==='1')>Yes</option>
          <option value="0" @selected(request('activo')==='0')>No</option>
        </select>
      </div>

      <div class="col-6 col-md-1">
        <label class="form-label">Per</label>
        <select name="per" class="form-select">
          @foreach([10,15,25,50,100] as $opt)
            <option value="{{ $opt }}" @selected((int)request('per',15)===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md d-flex gap-2">
        <button class="btn btn-outline-secondary btn-mat" type="submit">
          <i class="bi bi-funnel"></i> Apply
        </button>

        @if(request()->filled('q') || request()->filled('company_id') || request()->filled('role') || request()->filled('activo') || request()->filled('per'))
          <a href="{{ route('abm.users.index') }}" class="btn btn-outline-secondary btn-mat" title="Clear">
            <i class="bi bi-x-lg"></i>
          </a>
        @endif
      </div>
    </form>

    @php
      function sort_url_users($col){
        $params = request()->all();
        $isCur  = ($params['sort'] ?? 'name') === $col;
        $dir    = ($params['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $params['sort'] = $col;
        $params['dir']  = ($isCur && $dir==='asc') ? 'desc' : 'asc';
        return route('abm.users.index', $params);
      }
      function sort_icon_users($col){
        $curSort = request('sort','name');
        $curDir  = request('dir','asc') === 'desc' ? 'desc' : 'asc';
        if($curSort !== $col) return '<i class="bi bi-arrow-down-up sort-icon"></i>';
        return $curDir==='asc'
          ? '<i class="bi bi-arrow-up sort-icon"></i>'
          : '<i class="bi bi-arrow-down sort-icon"></i>';
      }
      function sort_class_users($col){
        return request('sort','name')===$col ? 'mat-sort active' : 'mat-sort';
      }
    @endphp

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>
              <a class="{{ sort_class_users('name') }}" href="{{ sort_url_users('name') }}">
                Name {!! sort_icon_users('name') !!}
              </a>
            </th>
            <th>
              <a class="{{ sort_class_users('email') }}" href="{{ sort_url_users('email') }}">
                Email {!! sort_icon_users('email') !!}
              </a>
            </th>
            <th>Company</th>
            <th>Roles</th>
            <th>
              <a class="{{ sort_class_users('activo') }}" href="{{ sort_url_users('activo') }}">
                Active {!! sort_icon_users('activo') !!}
              </a>
            </th>
            <th class="text-end" style="width:220px;">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($users as $user)
            <tr>
              <td class="fw-semibold">{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->company?->name ?? '—' }}</td>
              <td>
                @forelse($user->roles as $role)
                  <span class="badge text-bg-secondary me-1">{{ $role->name }}</span>
                @empty
                  <span class="text-muted">—</span>
                @endforelse
              </td>
              <td>
                @if($user->activo)
                  <span class="badge text-bg-success">Yes</span>
                @else
                  <span class="badge text-bg-secondary">No</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group" role="group">
                  <a href="{{ route('abm.users.show', $user) }}" class="btn btn-outline-secondary btn-sm btn-mat">View</a>
                  <a href="{{ route('abm.users.edit', $user) }}" class="btn btn-outline-primary btn-sm btn-mat">Edit</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No users</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center">
      <small class="text-muted">
        Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}
      </small>
      {{ $users->withQueryString()->links() }}
    </div>

  </div>
</div>
@endsection
