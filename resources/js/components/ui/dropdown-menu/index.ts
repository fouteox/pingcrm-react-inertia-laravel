import { DropdownMenu as DropdownMenuPrimitive } from 'bits-ui';
import Content from './dropdown-menu-content.svelte';
import Item from './dropdown-menu-item.svelte';
import Label from './dropdown-menu-label.svelte';
import Separator from './dropdown-menu-separator.svelte';

const Root = DropdownMenuPrimitive.Root;
const Trigger = DropdownMenuPrimitive.Trigger;
const Group = DropdownMenuPrimitive.Group;

export {
    Root as DropdownMenu,
    Content as DropdownMenuContent,
    Group as DropdownMenuGroup,
    Item as DropdownMenuItem,
    Label as DropdownMenuLabel,
    Separator as DropdownMenuSeparator,
    Trigger as DropdownMenuTrigger,
};
