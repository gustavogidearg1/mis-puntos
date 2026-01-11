<x-guest-layout>
    @php
        $appName = config('app.name', 'MisPuntos');
    @endphp

    <style>
        /* Fondo + centrado */
        .auth-wrap{
            min-height: calc(100vh - 80px);
            display: grid;
            place-items: center;
            padding: 28px 12px;
        }

        /* Card tipo material */
        .auth-card{
            width: 100%;
            max-width: 460px;
        }

        /* Header tipo mat */
        .auth-header{
            background: var(--sidebar-bg);
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.18);
        }

        .auth-subtitle{
            font-size: .88rem;
            opacity: .9;
            margin: 0;
        }

        /* Inputs */
        .input-icon{
            position:absolute;
            left:.85rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events:none;
            opacity:.55;
        }
        .ps-icon{ padding-left: 2.35rem !important; }

        /* Separador suave */
        .soft-divider{
            height: 1px;
            background: rgba(0,0,0,.08);
            margin: 18px 0;
        }

        /* Botón principal que combine con tu look */
        .btn-auth{
            border-radius: 12px;
            padding: .65rem .95rem;
            box-shadow: 0 6px 18px rgba(0,0,0,.10);
        }

        /* Footer */
        .auth-footer{
            background: rgba(0,0,0,.02);
            border-top: 1px solid rgba(0,0,0,.06);
        }
    </style>

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="card mat-card">
                {{-- HEADER (mat-header) --}}
<div class="mat-header auth-header">
    <div class="d-flex align-items-center gap-2">
        <img
            src="{{ asset('logos/ImgLogoCircular-SF.png') }}"
            alt="Logo {{ config('app.name') }}"
            width="36"
            height="36"
            style="border-radius:12px; object-fit:cover; box-shadow: 0 6px 16px rgba(0,0,0,.12);"
        />

        <div>
            <h3 class="mat-title text-white mb-0">{{ config('app.name','MisPuntos') }}</h3>
            <p class="auth-subtitle">Ingresá con tu cuenta</p>
        </div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="badge rounded-pill" style="background: rgba(255,255,255,.18);">
            <i class="bi bi-shield-lock me-1"></i> Acceso
        </span>
    </div>
</div>


                {{-- BODY --}}
                <div class="card-body p-4">
                    {{-- Status (ej: reset link enviado) --}}
                    @if (session('status'))
                        <div class="alert alert-success mat-alert mb-3" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Error general breve --}}
                    @if ($errors->any())
                        <div class="alert alert-danger mat-alert mb-3" role="alert">
                            Revisá los campos marcados.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3 position-relative">
                            <i class="bi bi-envelope input-icon"></i>
                            <div class="form-floating">
                                <input id="email" type="email"
                                       class="form-control ps-icon @error('email') is-invalid @enderror"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required autofocus autocomplete="username"
                                       placeholder="nombre@empresa.com">
                                <label for="email">Email</label>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="mb-2 position-relative">
                            <i class="bi bi-lock input-icon"></i>
                            <div class="form-floating">
                                <input id="password" type="password"
                                       class="form-control ps-icon @error('password') is-invalid @enderror"
                                       name="password"
                                       required autocomplete="current-password"
                                       placeholder="••••••••">
                                <label for="password">Contraseña</label>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Acciones password --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mat" id="togglePassword">
                                <i class="bi bi-eye"></i> Mostrar
                            </button>

                            @if (Route::has('password.request'))
                                <a class="link-primary fw-semibold" href="{{ route('password.request') }}">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>

                        {{-- Remember --}}
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                       {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>

                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> Sesión segura
                            </span>
                        </div>

                        <div class="soft-divider"></div>

                        {{-- Submit --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-auth" id="btnSubmit">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- FOOTER --}}
                <div class="card-footer auth-footer text-center small text-muted py-3">
                    © {{ date('Y') }} {{ $appName }} — Todos los derechos reservados
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const pwd = document.getElementById('password');
            const isText = pwd.type === 'text';
            pwd.type = isText ? 'password' : 'text';
            this.innerHTML = isText
                ? '<i class="bi bi-eye"></i> Mostrar'
                : '<i class="bi bi-eye-slash"></i> Ocultar';
        });

        document.getElementById('loginForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.querySelector('.spinner-border')?.classList.remove('d-none');
        });
    </script>
</x-guest-layout>
