import * as React from "react";
import { cn } from "@/lib/utils";

interface TableContainerProps extends React.HTMLAttributes<HTMLDivElement> {
    condensed?: boolean;
}

export function TableContainer({ className, children, condensed = false, ...props }: TableContainerProps) {
    return (
        <div
            className={cn(
                "rounded-lg bg-card shadow-sm",
                "overflow-x-auto",
                condensed && "[&_[data-slot=table-head]]:h-6 [&_[data-slot=table-head]]:py-0.5 [&_[data-slot=table-cell]]:py-0.5 [&_[data-slot=table-row]]:h-auto",
                "[&_[data-slot=table-cell]]:whitespace-nowrap",
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
}
