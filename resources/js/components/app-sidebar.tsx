"use client"

import * as React from "react"
import { CircleDot } from "lucide-react"
import { Link, usePage } from "@inertiajs/react"

import { NavMain } from "@/components/nav-main"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import { filterNavigationByPermissions, tmsNavigation } from "@/lib/navigation"
import { PageProps } from "@/types"

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { auth, url } = usePage<PageProps>().props
    const permissions = auth.user?.permissions ?? []
    const navigation = filterNavigationByPermissions(tmsNavigation, permissions)

    return (
    <Sidebar variant="inset" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={route('dashboard')}>
                <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                  <CircleDot className="size-4" />
                </div>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">Menkem TMS</span>
                  <span className="truncate text-xs">Tyre Management</span>
                </div>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        {navigation.map((group) => (
          <NavMain
            key={group.label}
            label={group.label}
            items={group.items.map((item) => ({
              ...item,
              isActive: url === item.url || url.startsWith(`${item.url}/`),
            }))}
          />
        ))}
      </SidebarContent>
      <SidebarFooter>
        <NavUser user={auth.user!} />
      </SidebarFooter>
    </Sidebar>
  )
}
