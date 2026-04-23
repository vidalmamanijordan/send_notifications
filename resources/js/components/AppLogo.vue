<script setup lang="ts">
import notifikaLogo from '../../images/siderbar/notifika_logo_oficial.svg';
</script>

<template>
    <div class="flex items-center justify-center">
        <img :src="notifikaLogo" alt="NotifiK" class="h-8 w-auto object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-bold text-base">Notifi<span class="font-black text-yellow-400" style="font-size:1.2em;letter-spacing:0.02em;">K</span></span>
    </div>
</template>
