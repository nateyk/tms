import { useEffect, useMemo, useRef, useState } from "react";
import { usePage } from "@inertiajs/react";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { PageProps } from "@/types";

type FlashProps = {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
};

type ToastType = keyof FlashProps;

type ToastMagicWindow = Window & {
    toastMagic?: Record<ToastType, (heading: string, description?: string, showCloseBtn?: boolean) => void>;
};

const titles: Record<ToastType, string> = {
    success: "Success",
    error: "Action failed",
    warning: "Check this",
    info: "Note",
};

export function FlashMessages() {
    const { flash, errors } = usePage<PageProps & { flash: FlashProps; errors?: Record<string, string | string[]> }>().props;
    const [fallbackMessages, setFallbackMessages] = useState<Array<[ToastType, string]>>([]);
    const shownKeyRef = useRef<string>("");
    const shownErrorKeyRef = useRef<string>("");

    const messages = useMemo(() => {
        return (["success", "error", "warning", "info"] as ToastType[])
            .map((type) => [type, flash?.[type]] as [ToastType, string | undefined])
            .filter((entry): entry is [ToastType, string] => Boolean(entry[1]));
    }, [flash?.success, flash?.error, flash?.warning, flash?.info]);

    useEffect(() => {
        const key = messages.map(([type, message]) => `${type}:${message}`).join("|");

        if (!key || key === shownKeyRef.current) {
            return;
        }

        shownKeyRef.current = key;

        const toastMagic = (window as ToastMagicWindow).toastMagic;

        if (!toastMagic) {
            setFallbackMessages(messages);
            return;
        }

        setFallbackMessages([]);

        messages.forEach(([type, message], index) => {
            window.setTimeout(() => {
                toastMagic[type]?.(titles[type], message, true);
            }, index * 250);
        });
    }, [messages]);

    useEffect(() => {
        const errorValues = Object.values(errors ?? {}).flat().filter(Boolean);
        const key = errorValues.join("|");

        if (!key || key === shownErrorKeyRef.current) {
            return;
        }

        shownErrorKeyRef.current = key;

        const toastMagic = (window as ToastMagicWindow).toastMagic;
        const message = errorValues.length === 1
            ? String(errorValues[0])
            : `${errorValues.length} fields need attention. Please check the highlighted inputs.`;

        if (!toastMagic) {
            setFallbackMessages([["warning", message]]);
            return;
        }

        toastMagic.warning?.("Check the form", message, true);
    }, [errors]);

    if (fallbackMessages.length === 0) {
        return null;
    }

    return (
        <div className="mb-4 space-y-2">
            {fallbackMessages.map(([type, message]) => (
                <Alert key={`${type}:${message}`} variant={type === "error" ? "destructive" : "default"}>
                    <AlertDescription>{message}</AlertDescription>
                </Alert>
            ))}
        </div>
    );
}
