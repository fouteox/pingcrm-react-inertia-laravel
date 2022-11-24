import { Link } from "@inertiajs/inertia-react";
import clsx from "clsx";

export default ({ href, type = "button", style, className, children }) => {
    const classStyle = clsx(className, {
        "px-6 py-3 rounded bg-indigo-600 text-white text-sm leading-4 font-bold whitespace-nowrap hover:bg-orange-400 focus:bg-orange-400":
            style == "btn",
        "ml-3 text-gray-500 hover:text-gray-700 focus:text-indigo-500 text-sm":
            style === "anchor",
    });

    return (
        <Link href={href} className={classStyle} type={type}>
            {children}
        </Link>
    );
};
