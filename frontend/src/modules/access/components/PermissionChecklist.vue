<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    required: true,
  },
  permissions: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue'])

const selectedPermissions = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const permissionGroups = computed(() => {
  const groups = new Map()

  for (const permission of props.permissions) {
    if (!groups.has(permission.module)) groups.set(permission.module, [])
    groups.get(permission.module).push(permission)
  }

  return [...groups.entries()].map(([key, items]) => ({ key, items }))
})
</script>

<template>
  <div class="permission-groups">
    <fieldset v-for="group in permissionGroups" :key="group.key">
      <legend>{{ group.key }}</legend>
      <label v-for="permission in group.items" :key="permission.key" class="check-row">
        <input
          v-model="selectedPermissions"
          type="checkbox"
          :value="permission.key"
        >
        <span>
          <strong>{{ permission.name }}</strong>
          <small>{{ permission.key }}</small>
        </span>
      </label>
    </fieldset>
  </div>
</template>

<style scoped>
.permission-groups {
  margin: 22px 0;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

fieldset {
  min-width: 0;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
}

legend {
  padding: 0 7px;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
}

.check-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: var(--text-muted);
}

.check-row + .check-row {
  margin-top: 11px;
}

.check-row input {
  margin-top: 4px;
}

.check-row span {
  display: grid;
  gap: 2px;
}

.check-row strong {
  color: var(--text);
  font-size: 0.84rem;
}

.check-row small {
  overflow-wrap: anywhere;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
}

@media (max-width: 900px) {
  .permission-groups {
    grid-template-columns: 1fr;
  }
}
</style>
