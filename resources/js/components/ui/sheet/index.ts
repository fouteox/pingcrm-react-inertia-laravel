import { Dialog as SheetPrimitive } from 'bits-ui';
import Content from './sheet-content.svelte';

const Root = SheetPrimitive.Root;
const Title = SheetPrimitive.Title;
const Description = SheetPrimitive.Description;

export { Root as Sheet, Content as SheetContent, Description as SheetDescription, Title as SheetTitle };
