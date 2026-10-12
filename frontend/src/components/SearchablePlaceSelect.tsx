import {useEffect, useRef, useState} from 'react';
import {Check, ChevronDown, MapPin} from 'lucide-react';
import {tr} from '../i18n';
import {filterPlaces} from './place-options';
import type {PlaceOption} from './place-options';

type Props = {id: string; value: string; options: PlaceOption[]; placeholder: string; onChange: (value: string) => void};

export function SearchablePlaceSelect({id, value, options, placeholder, onChange}: Props) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [active, setActive] = useState(0);
  const root = useRef<HTMLDivElement>(null);
  const input = useRef<HTMLInputElement>(null);
  const selected = options.find(option => option.value === value);
  const results = filterPlaces(options, query);
  const index = Math.min(active, Math.max(0, results.length - 1));
  const validationMessage = selected ? '' : tr('Choose a place from the list.');

  useEffect(() => { input.current?.setCustomValidity(validationMessage); }, [validationMessage]);
  useEffect(() => {
    if (!open) return;
    function dismiss(event: PointerEvent) {
      if (!root.current?.contains(event.target as Node)) setOpen(false);
    }
    document.addEventListener('pointerdown', dismiss);
    return () => document.removeEventListener('pointerdown', dismiss);
  }, [open]);
  useEffect(() => {
    if (open) document.getElementById(`${id}-option-${index}`)?.scrollIntoView({block: 'nearest'});
  }, [open, index, query, id]);

  function show() { setQuery(''); setActive(0); setOpen(true); }
  function choose(option: PlaceOption) { onChange(option.value); setQuery(''); setOpen(false); }

  return <div ref={root} className="searchable-place" onBlur={event => {
    if (!event.currentTarget.contains(event.relatedTarget as Node | null)) setOpen(false);
  }}>
    <div className="combobox-control">
      <input ref={input} id={id} role="combobox" autoComplete="off" required
        aria-autocomplete="list" aria-expanded={open} aria-controls={`${id}-listbox`}
        aria-activedescendant={open && results.length ? `${id}-option-${index}` : undefined}
        value={open ? query : selected?.label || ''} placeholder={placeholder}
        onFocus={show} onClick={() => { if (!open) show(); }}
        onChange={event => { setQuery(event.target.value); setActive(0); setOpen(true); onChange(''); }}
        onKeyDown={event => {
          if (event.key === 'Escape') { if (open) { event.preventDefault(); event.stopPropagation(); setOpen(false); } return; }
          if (event.key === 'Tab') { setOpen(false); return; }
          if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!open) { show(); return; }
            setActive(results.length ? (index + (event.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length : 0);
          } else if (open && (event.key === 'Home' || event.key === 'End')) {
            event.preventDefault(); setActive(event.key === 'Home' ? 0 : Math.max(0, results.length - 1));
          } else if (open && event.key === 'Enter') {
            event.preventDefault(); if (results[index]) choose(results[index]);
          }
        }}/>
      <button type="button" className="combobox-toggle" tabIndex={-1} aria-label={tr('Show places')}
        onMouseDown={event => event.preventDefault()}
        onClick={() => { if (open) setOpen(false); else { input.current?.focus(); show(); } }}>
        <ChevronDown size={18} aria-hidden="true"/>
      </button>
    </div>
    {open && <div className="combobox-menu">
      <ul id={`${id}-listbox`} role="listbox" aria-labelledby={`${id}-label`}>
        {results.map((option, position) => <li key={option.value} id={`${id}-option-${position}`}
          role="option" aria-selected={option.value === value}
          className={`combobox-option ${position === index ? 'active' : ''}`}
          onMouseDown={event => event.preventDefault()} onClick={() => choose(option)}
          onMouseMove={() => setActive(position)}>
          <MapPin size={17} aria-hidden="true"/>
          <span><strong>{option.label}</strong><small>{option.description}</small>
            {option.verified && <small className="verified-place">{tr('Verified landmark')}</small>}</span>
          {option.value === value && <Check size={17} aria-hidden="true"/>}
        </li>)}
      </ul>
      {!results.length && <p className="combobox-empty" role="status">{tr('No places found. Try another name.')}</p>}
    </div>}
  </div>;
}