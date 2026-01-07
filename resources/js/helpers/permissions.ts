import { usePage } from '@inertiajs/vue3'

export function can(permission: string) {
    const permissions = usePage().props.auth_permissions as string[]
    return permissions.includes(permission)
}
