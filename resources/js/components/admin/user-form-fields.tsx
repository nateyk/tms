import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { InputError } from "@/components/ui/input-error";
import { Label } from "@/components/ui/label";

type UserFormData = {
    name: string;
    email: string;
    password: string;
    roles: string[];
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
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Name</Label>
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

            <div className="grid gap-2">
                <Label htmlFor="password">
                    Password{passwordRequired ? "" : " (leave blank to keep current)"}
                </Label>
                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData("password", e.target.value)}
                    required={passwordRequired}
                    autoComplete={passwordRequired ? "new-password" : "off"}
                />
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-3">
                <Label>Roles</Label>
                <div className="grid gap-3 sm:grid-cols-2">
                    {roleOptions.map((role) => (
                        <label
                            key={role}
                            className="flex items-center gap-2 rounded-md border p-3 text-sm"
                        >
                            <Checkbox
                                checked={data.roles.includes(role)}
                                onCheckedChange={(checked) =>
                                    toggleRole(role, checked === true)
                                }
                            />
                            <span>{role}</span>
                        </label>
                    ))}
                </div>
                <InputError message={errors.roles} />
            </div>
        </>
    );
}
