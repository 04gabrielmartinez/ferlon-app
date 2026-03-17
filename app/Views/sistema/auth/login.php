<?php
use App\Core\Settings;

$paso2fa = is_array($estado2fa ?? null);
$correoBloqueado = $paso2fa ? (string) ($estado2fa['correo'] ?? '') : '';
$twoFactorEnabled = (bool) ($twoFactorEnabled ?? false);
$logoPath = trim((string) Settings::get('logo_path', ''));
if ($logoPath !== '' && $logoPath[0] !== '/') {
    $logoPath = '/' . ltrim($logoPath, '/');
}
?>
<section class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
    <main class="my-8 w-full max-w-md rounded-xl bg-white px-6 py-10 shadow-sm ring-1 ring-gray-950/5 sm:px-10 sm:py-12" x-data="{showPass:false}">
        <header class="mb-6 flex flex-col items-center">
            <?php if ($logoPath !== ''): ?>
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" class="mb-3" style="max-height:40px;">
            <?php else: ?>
                <span class="mb-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">FERLON</span>
            <?php endif; ?>
            <h1 class="text-center text-2xl font-bold tracking-tight text-gray-900">
                <?= $paso2fa ? 'Verificacion 2FA' : 'Iniciar sesion' ?>
            </h1>
            <p class="mt-2 text-center text-sm text-gray-500">
                <?= $paso2fa ? 'Ingresa tu codigo para continuar.' : 'Accede con tu usuario o correo.' ?>
            </p>
        </header>

        <?php if (!empty($error)): ?>
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <?= htmlspecialchars((string) $error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($info)): ?>
            <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-700">
                <?= htmlspecialchars((string) $info) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/login" class="space-y-4" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) $csrf) ?>">
            <input type="hidden" name="paso" value="<?= $paso2fa ? 'codigo' : 'credenciales' ?>">

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Usuario o correo</label>
                <input
                    type="text"
                    id="email"
                    name="email"
                    placeholder="gmartinez o gmartinez@cellphone.do"
                    value="<?= htmlspecialchars($correoBloqueado) ?>"
                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-200"
                    <?= $paso2fa ? 'disabled' : 'required' ?>
                >
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Contrasena</label>
                <div class="relative">
                    <input
                        :type="showPass ? 'text' : 'password'"
                        id="password"
                        name="password"
                        placeholder="********"
                        value="<?= $paso2fa ? '********' : '' ?>"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-700 outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-200"
                        <?= $paso2fa ? 'disabled' : 'required' ?>
                    >
                    <button type="button" @click="showPass=!showPass" class="absolute right-2 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" aria-label="Mostrar u ocultar contrasena">
                        <i class="bi text-base" :class="showPass ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
            </div>

            <?php if ($paso2fa): ?>
                <div>
                    <label for="codigo_2fa" class="mb-2 block text-sm font-medium text-gray-700">Codigo 2FA</label>
                    <input
                        type="text"
                        id="codigo_2fa"
                        name="codigo_2fa"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        placeholder="000000"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-200"
                        required
                    >
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800">Verificar</button>
                    <a href="/login?reiniciar=1" class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-medium text-gray-700">Usar otra cuenta</a>
                </div>
            <?php else: ?>
                <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800">
                    Entrar
                </button>
            <?php endif; ?>
        </form>

        <?php if (!$paso2fa): ?>
            <p class="mt-5 text-center text-xs text-gray-400">
                <?= $twoFactorEnabled ? '2FA activo.' : '2FA desactivado por ahora.' ?>
            </p>
        <?php endif; ?>
    </main>
</section>
