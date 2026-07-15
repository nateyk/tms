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
import { InputError } from "@/components/ui/input-error";

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
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
                <Card className="mx-auto w-[min(92vw,420px)] border-slate-200 bg-white text-slate-950 shadow-sm">
                    <CardHeader className="pb-5">
                        <CardTitle className="text-2xl font-semibold text-slate-950">Menkem TMS</CardTitle>
                        <CardDescription className="text-slate-500">
                            Sign in to the tyre management dashboard
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {status && (
                            <div className="mb-4 font-medium text-sm text-green-600">
                                {status}
                            </div>
                        )}

                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="email" className="text-slate-800">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="m@example.com"
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
                                    <Link
                                        href={route("password.request")}
                                        className="ml-auto inline-block text-sm font-medium text-slate-600 underline hover:text-slate-950"
                                    >
                                        Forgot your password?
                                    </Link>
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
                            <Button type="submit" className="w-full bg-slate-950 text-white hover:bg-slate-800">
                                Login
                            </Button>
                        </div>
                        <div className="mt-5 text-center text-sm text-slate-500">
                            Don&apos;t have an account?{" "}
                            <Link href="/register" className="font-medium text-slate-700 underline hover:text-slate-950">
                                Sign up
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </GuestLayout>
    );
}
