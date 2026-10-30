import { AlertDialog as AlertDialogPrimitive } from 'bits-ui';
import Cancel from './alert-dialog-cancel.svelte';
import Content from './alert-dialog-content.svelte';
import Description from './alert-dialog-description.svelte';
import Footer from './alert-dialog-footer.svelte';
import Title from './alert-dialog-title.svelte';

const Root = AlertDialogPrimitive.Root;
const Trigger = AlertDialogPrimitive.Trigger;

export {
    Root as AlertDialog,
    Cancel as AlertDialogCancel,
    Content as AlertDialogContent,
    Description as AlertDialogDescription,
    Footer as AlertDialogFooter,
    Title as AlertDialogTitle,
    Trigger as AlertDialogTrigger,
};
