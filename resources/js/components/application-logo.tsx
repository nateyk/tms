import { ImgHTMLAttributes } from "react";

export default function ApplicationLogo(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/images/menkem-logo.svg"
            alt="Menkem International Business PLC"
        />
    );
}
