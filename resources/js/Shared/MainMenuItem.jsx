import React from "react";
import { Link } from "@inertiajs/inertia-react";
import Icon from "@/Shared/Icon";
import clsx from "clsx";

export default ({ icon, link, text }) => {
    const isActive = route().current(link + "*");

    const iconClasses = clsx("w-4 h-4 mr-2", {
        "text-white fill-current": isActive,
        "text-indigo-400 group-hover:text-white fill-current": !isActive,
    });

    const textClasses = clsx({
        "text-white": isActive,
        "text-indigo-200 group-hover:text-white": !isActive,
    });

    return (
        <div className="mb-2">
            <Link
                href={route(link)}
                className="flex text-indigo-300 items-center group py-3"
            >
                <Icon name={icon} className={iconClasses} />
                <div className={textClasses}>{text}</div>
            </Link>
        </div>
    );
};
