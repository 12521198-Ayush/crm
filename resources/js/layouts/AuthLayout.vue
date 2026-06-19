<template>
  <div class="min-h-screen grid lg:grid-cols-2 bg-slate-50">
    <!-- Left hero panel -->
    <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-indigo-700 text-white p-12">
      <div class="absolute inset-0 opacity-20 pointer-events-none"
           style="background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.35) 0, transparent 40%), radial-gradient(circle at 80% 70%, rgba(255,255,255,0.25) 0, transparent 45%);"></div>

      <div class="relative flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-lg font-bold ring-1 ring-white/25">N</div>
        <div>
          <div class="text-lg font-semibold leading-none">Ninja CRM</div>
          <div class="text-xs text-white/70">Lead intelligence platform</div>
        </div>
      </div>

      <div class="relative flex-1 flex items-center justify-center my-10">
        <div class="relative">
          <div class="absolute -inset-10 rounded-full bg-white/10 blur-3xl"></div>
          <div class="absolute -inset-4 rounded-full bg-white/10 blur-xl"></div>
          <img src="/images/login-hero.png" alt="Secure login"
               class="relative w-[420px] max-w-full drop-shadow-2xl select-none" />
        </div>
      </div>

      <div class="relative space-y-4">
        <h2 class="text-3xl font-bold leading-tight">Secure access to your sales pipeline.</h2>
        <p class="text-white/80 max-w-md">Capture leads from Meta, Google Ads, and IVR. Assign, track, and close — all in one place.</p>
        <div class="flex items-center gap-6 pt-2 text-sm text-white/80">
          <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> End-to-end encrypted</div>
          <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-300"></span> Role-based access</div>
        </div>
      </div>
    </div>

    <!-- Right form panel -->
    <div class="flex items-center justify-center px-6 py-12">
      <div class="w-full max-w-md">
        <!-- Mobile hero (top) -->
        <div class="lg:hidden mb-6 flex items-center justify-center">
          <img src="/images/login-hero.png" alt="Secure login"
               class="w-48 max-w-full select-none" />
        </div>

        <router-view />
      </div>
    </div>
  </div>
</template>
