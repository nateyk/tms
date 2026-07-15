import ApplicationLogo from "@/components/application-logo";
import { Link } from "@inertiajs/react";
import { PropsWithChildren } from "react";

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
            <div className="rounded-2xl bg-white px-6 py-4 shadow-sm ring-1 ring-slate-200">
                <Link href="/">
                    <ApplicationLogo className="h-14 w-auto" />
                </Link>
            </div>

            <div className="w-full sm:max-w-md mt-6 px-6 py-4">{children}</div>
        </div>
    );
}
