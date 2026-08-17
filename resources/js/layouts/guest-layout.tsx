import ApplicationLogo from "@/components/application-logo";
import { Link } from "@inertiajs/react";
import { PropsWithChildren } from "react";

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 text-slate-950 sm:px-6">
            <div className="w-full max-w-md">
                <Link
                    href="/"
                    className="mx-auto flex w-fit items-center gap-4 rounded-md px-2 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-700 focus-visible:ring-offset-4"
                >
                    <ApplicationLogo className="h-11 w-auto sm:h-12" />
                    <span className="h-9 w-px bg-slate-200" aria-hidden="true" />
                    <span className="min-w-0">
                        <span className="block text-base font-semibold leading-5">Menkem TMS</span>
                        <span className="block text-xs text-slate-500">Tyre Management</span>
                    </span>
                </Link>

                <main className="mt-6">{children}</main>

                <p className="mt-5 text-center text-xs text-slate-500">
                    Menkem International Business PLC
                </p>
            </div>
        </div>
    );
}
