import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { CircleUserRound, KeyRound, ShieldCheck } from "lucide-react";

type UserFormData = {
    name: string;
    email: string;
    password: string;
    roles: string[];
    is_active: boolean;
};

type UserFormFieldsProps = {
    roleOptions: string[];
    errors: Partial<Record<keyof UserFormData | "roles.0", string>>;
    data: UserFormData;
    setData: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void;
    passwordRequired?: boolean;
};

export function UserFormFields({
    roleOptions,
    errors,
    data,
    setData,
    passwordRequired = false,
}: UserFormFieldsProps) {
    const toggleRole = (role: string, checked: boolean) => {
        if (checked) {
            setData("roles", [...data.roles, role]);
            return;
        }

        setData(
            "roles",
            data.roles.filter((item) => item !== role),
        );
    };

    return (
        <div className="divide-y">
            <section className="grid gap-5 pb-6 md:grid-cols-[180px_1fr]" aria-labelledby="identity-heading">
                <div>
                    <div className="flex items-center gap-2">
                        <CircleUserRound className="h-4 w-4 text-muted-foreground" />
                        <h2 id="identity-heading" className="text-sm font-semibold">Identity</h2>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">Account and sign-in details.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Full name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            required
                            autoComplete="name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            required
                            autoComplete="username"
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>
            </section>

            <section className="grid gap-5 py-6 md:grid-cols-[180px_1fr]" aria-labelledby="password-heading">
                <div>
                    <div className="flex items-center gap-2">
                        <KeyRound className="h-4 w-4 text-muted-foreground" />
                        <h2 id="password-heading" className="text-sm font-semibold">Password</h2>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {passwordRequired ? "Set initial account access." : "Leave blank to keep the current password."}
                    </p>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">
                        {passwordRequired ? "Temporary password" : "New password (optional)"}
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        required={passwordRequired}
                        autoComplete="new-password"
                    />
                    <p className="text-xs text-muted-foreground">
                        Use at least 12 characters with upper and lowercase letters, a number, and a symbol.
                    </p>
                    <InputError message={errors.password} />
                </div>
            </section>

            <section className="grid gap-5 pt-6 md:grid-cols-[180px_1fr]" aria-labelledby="access-heading">
                <div>
                    <div className="flex items-center gap-2">
                        <ShieldCheck className="h-4 w-4 text-muted-foreground" />
                        <h2 id="access-heading" className="text-sm font-semibold">Access</h2>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">Role permissions and account status.</p>
                </div>
                <div className="space-y-5">
                    <div className="grid gap-3">
                        <Label>Assigned roles</Label>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {roleOptions.map((role) => (
                                <label
                                    key={role}
                                    className={`flex min-h-11 cursor-pointer items-center gap-3 rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted/50 ${
                                        data.roles.includes(role)
                                            ? "border-indigo-300 bg-indigo-50 dark:border-indigo-500/40 dark:bg-indigo-500/10"
                                            : "bg-background"
                                    }`}
                                >
                                    <Checkbox
                                        checked={data.roles.includes(role)}
                                        onCheckedChange={(checked) =>
                                            toggleRole(role, checked === true)
                                        }
                                    />
                                    <span className="font-medium">{role.replaceAll("_", " ")}</span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.roles} />
                    </div>

                    <div className="flex items-center justify-between gap-4 rounded-md border bg-muted/20 px-4 py-3">
                        <div>
                            <Label htmlFor="is_active" className="cursor-pointer">Account active</Label>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Deactivation signs the user out and blocks future sign-ins.
                            </p>
                        </div>
                        <Switch
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData("is_active", checked)}
                            aria-label="Account active"
                        />
                    </div>
                    <InputError message={errors.is_active} />
                </div>
            </section>
        </div>
    );
}
