import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import * as React from 'react';

function TableContainer({ className, ...props }: React.ComponentProps<'table'>) {
    return (
        <ScrollArea className="overflow-contain grid w-full grid-cols-1">
            <table data-slot="table" className={cn('w-full caption-bottom text-sm', className)} {...props} />
            <ScrollBar orientation="horizontal" />
            <ScrollBar orientation="vertical" />
        </ScrollArea>
    );
}

export { TableContainer };
