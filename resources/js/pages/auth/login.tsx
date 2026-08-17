import { FormEventHandler, useEffect } from "react";
import GuestLayout from "@/layouts/guest-layout";
import { Head, Link, useForm } from "@inertiajs/react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { InputError } from "@/components/ui/input-error";
import { Loader2, LockKeyhole } from "lucide-react";

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    useEffect(() => {
        return () => {
            reset("password");
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("login"));
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <form onSubmit={submit}>
                <Card className="border-slate-200 bg-white text-slate-950 shadow-sm">
                    <CardHeader className="space-y-2 border-b border-slate-100 pb-5">
                        <div className="flex items-center gap-2 text-xs font-semibold uppercase text-indigo-800">
                            <LockKeyhole className="h-4 w-4" />
                            Controlled access
                        </div>
                        <CardTitle className="text-2xl font-semibold text-slate-950">
                            Sign in
                        </CardTitle>
                        <CardDescription className="text-slate-500">
                            Use your assigned company account to continue.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-5">
                        {status && (
                            <div
                                role="status"
                                className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-800"
                            >
                                {status}
                            </div>
                        )}

                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email" className="text-slate-800">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="name@menkemintl.com"
                                    className="border-slate-200 bg-white text-slate-950 placeholder:text-slate-400"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password" className="text-slate-800">Password</Label>
                                    {canResetPassword && (
                                        <Link
                                            href={route("password.request")}
                                            className="ml-auto inline-block text-sm font-medium text-slate-600 underline hover:text-slate-950"
                                        >
                                            Forgot your password?
                                        </Link>
                                    )}
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    className="border-slate-200 bg-white text-slate-950"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData("remember", checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="remember"
                                    className="cursor-pointer text-sm font-normal text-slate-600"
                                >
                                    Keep me signed in on this device
                                </Label>
                            </div>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-slate-950 text-white hover:bg-slate-800"
                            >
                                {processing && <Loader2 className="h-4 w-4 animate-spin" />}
                                {processing ? "Signing in..." : "Sign in"}
                            </Button>
                        </div>
                        {canRegister && (
                            <div className="mt-5 text-center text-sm text-slate-500">
                                Don&apos;t have an account?{" "}
                                <Link href={route("register")} className="font-medium text-slate-700 underline hover:text-slate-950">
                                    Sign up
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </form>
        </GuestLayout>
    );
}
