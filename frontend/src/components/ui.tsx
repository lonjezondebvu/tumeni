import * as Dialog from '@radix-ui/react-dialog';
import {X} from 'lucide-react';
import type {ButtonHTMLAttributes,ReactNode} from 'react';
import {clsx} from 'clsx';
import {twMerge} from 'tailwind-merge';
export function Button({className,variant='primary',...props}:ButtonHTMLAttributes<HTMLButtonElement>&{variant?:'primary'|'secondary'|'ghost'}){return <button className={twMerge(clsx('button',variant,className))} {...props}/>;}
export function Modal({open,onOpenChange,title,description,children}:{open:boolean;onOpenChange:(v:boolean)=>void;title:string;description:string;children:ReactNode}){return <Dialog.Root open={open} onOpenChange={onOpenChange}><Dialog.Portal><Dialog.Overlay className="overlay"/><Dialog.Content className="dialog"><Dialog.Title>{title}</Dialog.Title><Dialog.Description>{description}</Dialog.Description><Dialog.Close className="close" aria-label="Close"><X size={20}/></Dialog.Close>{children}</Dialog.Content></Dialog.Portal></Dialog.Root>;}