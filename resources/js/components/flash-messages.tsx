import { useEffect } from "react";
import { usePage } from "@inertiajs/react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { PageProps } from "@/types";

type FlashProps = {
    success?: string;
    error?: string;
};

export function FlashMessages() {
    const { flash } = usePage<PageProps & { flash: FlashProps }>().props;

    useEffect(() => {
        if (flash?.success || flash?.error) {
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    }, [flash?.success, flash?.error]);

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="mb-4 space-y-2">
            {flash.success && (
                <Alert>
                    <AlertDescription>{flash.success}</AlertDescription>
                </Alert>
            )}
            {flash.error && (
                <Alert variant="destructive">
                    <AlertDescription>{flash.error}</AlertDescription>
                </Alert>
            )}
        </div>
    );
}
