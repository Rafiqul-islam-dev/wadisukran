<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { type NavItem } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'


interface Props {
    items: NavItem[]
    currentPath: string
}
const { auth_permissions } = usePage().props;
const props = defineProps<Props>()
const isActive = (href?: string) => {
    if (!href) return false

    return props.currentPath === href ||
        props.currentPath.startsWith(href + '?')
}

// A group is visible if the user has its own permission OR any child's permission
// (so a user can be given a single menu such as "Registration & Agreement" without the whole group).
const canSee = (item: NavItem) => {
    const perms = auth_permissions as string[]
    if (perms.includes(item.permission as string)) return true
    // opt-in per group (item.showIfAnyChild) so other groups keep their original visibility rules
    return !!item.showIfAnyChild && !!item.items?.some((child) => perms.includes(child.permission as string))
}

const isExpanded = (items?: NavItem[]) => {
    if (!items) return false
    return items.some(item => isActive(item.href))
}

</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem v-if="canSee(item)">
                    <!-- Single item -->
                    <SidebarMenuButton v-if="!item.items" as-child :class="{ 'bg-gray-300/50': isActive(item.href) }">
                        <Link :href="item.href">
                            <component :is="item.icon" v-if="item.icon" />
                            <span class="font-bold">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>

                    <!-- Dropdown -->
                    <Collapsible v-else class="group/collapsible" :default-open="isExpanded(item.items)">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :class="{ 'bg-muted font-semibold bg-gray-300/50': isExpanded(item.items) }">
                                <component :is="item.icon" v-if="item.icon" />
                                <span class="font-bold">{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                    <div v-if="auth_permissions.includes(subItem.permission)">
                                        <SidebarMenuSubButton as-child
                                            :class="{ 'bg-gray-300/50': isActive(subItem.href) }">
                                            <Link :href="subItem.href" class="text-white">
                                                <component :is="subItem.icon" v-if="subItem.icon" />
                                                <span >{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </div>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </template>

        </SidebarMenu>
    </SidebarGroup>
</template>
