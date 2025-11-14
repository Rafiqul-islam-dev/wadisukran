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
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

interface Props {
    items: NavItem[]
}

defineProps<Props>()
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- Regular menu item (no subitems) -->
                <SidebarMenuButton v-if="!item.items" as-child>
                    <Link :href="item.href">
                    <component :is="item.icon" v-if="item.icon" />
                    <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <!-- Dropdown menu item (has subitems) -->
                <Collapsible v-else class="group/collapsible">
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton>
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                <SidebarMenuSubButton as-child>
                                    <Link :href="subItem.href">
                                    <component :is="subItem.icon" v-if="subItem.icon" />
                                    <span>{{ subItem.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>