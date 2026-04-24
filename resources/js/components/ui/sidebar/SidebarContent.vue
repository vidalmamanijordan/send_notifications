<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { ref } from "vue"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const hovered = ref(false)
</script>

<template>
  <div
    data-slot="sidebar-content"
    data-sidebar="content"
    :class="cn('flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden', props.class)"
    :data-scrollhover="hovered || undefined"
    @mouseenter="hovered = true"
    @mouseleave="hovered = false"
  >
    <slot />
  </div>
</template>
