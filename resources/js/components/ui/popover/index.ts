import { Popover as PopoverPrimitive } from 'bits-ui';
import Content from './popover-content.svelte';

const Root = PopoverPrimitive.Root;
const Trigger = PopoverPrimitive.Trigger;

export { Root as Popover, Content as PopoverContent, Trigger as PopoverTrigger };
