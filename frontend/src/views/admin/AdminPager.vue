<template>
<div v-if="meta && meta.last_page > 1" class="pager">
  <button class="ghost" type="button" :disabled="meta.current_page <= 1" @click="$emit('page', meta.current_page - 1)">&larr; Prev</button>
  <span class="pg">Page {{ meta.current_page }} of {{ meta.last_page }} &middot; {{ meta.total }} total</span>
  <button class="ghost" type="button" :disabled="meta.current_page >= meta.last_page" @click="$emit('page', meta.current_page + 1)">Next &rarr;</button>
</div>
</template>

<script setup>
defineProps({ meta: { type: Object, default: null } })
defineEmits(['page'])
</script>

<style scoped>
.pager{display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:14px 0 6px;flex-wrap:wrap}
.pg{font-size:13px;font-weight:600;color:var(--muted)}
button:disabled{opacity:.45;cursor:default}
</style>
