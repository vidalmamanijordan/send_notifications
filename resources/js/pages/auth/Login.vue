<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, Lock, Mail } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    whatsappUrl?: string | null;
}>();
</script>

<template>
    <AuthBase
        title="Bienvenido de vuelta"
        description="Ingresa tus credenciales para acceder al sistema"
    >
        <Head title="Iniciar sesión" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-5">
                <!-- Email -->
                <div class="grid gap-1.5">
                    <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Correo electrónico
                    </Label>
                    <div class="relative">
                        <Mail class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#68c8fb]" />
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="correo@upeu.edu.pe"
                            class="h-11 w-full rounded-xl border border-[#68c8fb]/30 bg-[#04395a]/5 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 outline-none focus:border-[#68c8fb] focus:bg-white focus:ring-3 focus:ring-[#68c8fb]/20 dark:bg-[#04395a]/20 dark:text-white dark:placeholder:text-gray-500 dark:focus:bg-[#04395a]/30"
                            :class="errors.email ? 'border-red-400 focus:border-red-400 focus:ring-red-200' : ''"
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <!-- Contraseña -->
                <div class="grid gap-1.5">
                    <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Contraseña
                    </Label>
                    <div class="relative">
                        <Lock class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#68c8fb]" />
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="h-11 w-full rounded-xl border border-[#68c8fb]/30 bg-[#04395a]/5 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm transition-all duration-200 outline-none focus:border-[#68c8fb] focus:bg-white focus:ring-3 focus:ring-[#68c8fb]/20 dark:bg-[#04395a]/20 dark:text-white dark:placeholder:text-gray-500 dark:focus:bg-[#04395a]/30"
                            :class="errors.password ? 'border-red-400 focus:border-red-400 focus:ring-red-200' : ''"
                        />
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <!-- Recordarme -->
                <div class="flex items-center">
                    <Label for="remember" class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-600 dark:text-gray-400">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Recordarme en este dispositivo</span>
                    </Label>
                </div>

                <!-- Botón Ingresar -->
                <button
                    type="submit"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                    class="relative mt-1 flex h-11 w-full items-center justify-center gap-2 overflow-hidden rounded-xl text-sm font-semibold tracking-wide shadow-md transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-70"
                    style="background: linear-gradient(135deg, #04395a 0%, #065c8e 100%); color: #68c8fb;"
                    onmouseover="this.style.filter='brightness(1.12)'"
                    onmouseout="this.style.filter=''"
                >
                    <Spinner v-if="processing" class="h-4 w-4" />
                    <KeyRound v-else class="h-4 w-4" />
                    {{ processing ? 'Verificando...' : 'Ingresar al sistema' }}
                </button>
            </div>

            <!-- Contacto TI -->
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                ¿No tienes una cuenta?
                <a
                    :href="whatsappUrl ?? 'https://web.whatsapp.com'"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-gray-400 underline underline-offset-4 transition-colors hover:text-gray-600"
                    :tabindex="5"
                >
                    Contáctanos
                </a>
            </div>
        </Form>
    </AuthBase>
</template>
