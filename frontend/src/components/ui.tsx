// translated-ui-v1
import {tr} from '../i18n';
import * as Dialog from '@radix-ui/react-dialog';
import {X,LoaderCircle} from 'lucide-react';
import type {ButtonHTMLAttributes,ReactNode} from 'react';
import {clsx} from 'clsx';
import {twMerge} from 'tailwind-merge';
export function Button({className,variant='primary',loading=false,disabled,children,...props}:ButtonHTMLAttributes<HTMLButtonElement>&{variant?:'primary'|'secondary'|'ghost';loading?:boolean}){return <button className={twMerge(clsx('button',variant,className))} disabled={disabled||loading} aria-busy={loading||undefined} {...props}>{loading&&<LoaderCircle size={18} className="button-spinner" aria-hidden="true"/>}{children}</button>;}
export function Modal({open,onOpenChange,title,description,children}:{open:boolean;onOpenChange:(v:boolean)=>void;title:string;description:string;children:ReactNode}){return <Dialog.Root open={open} onOpenChange={onOpenChange}><Dialog.Portal><Dialog.Overlay className="overlay"/><Dialog.Content className="dialog"><Dialog.Title>{title}</Dialog.Title><Dialog.Description>{description}</Dialog.Description><Dialog.Close className="close" aria-label={tr("Close")}><X size={20}/></Dialog.Close>{children}</Dialog.Content></Dialog.Portal></Dialog.Root>;}
export function PageHeader({title,description}:{title:string;description?:string}){return <header className="page-heading"><h1>{title}</h1>{description&&<p>{description}</p>}</header>;}
