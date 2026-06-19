import { useEffect, useRef, useState } from 'react';
import { apiFetch } from '../lib/api';

type Props = {
  value: string;
  onChange: (value: string) => void;
  required?: boolean;
};

export default function CityAutocomplete({ value, onChange, required }: Props) {
  const [query, setQuery] = useState(value);
  const [suggestions, setSuggestions] = useState<string[]>([]);
  const [open, setOpen] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout>>();

  useEffect(() => {
    setQuery(value);
  }, [value]);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (query.length < 2) {
      setSuggestions([]);
      return;
    }
    debounceRef.current = setTimeout(async () => {
      try {
        const data = await apiFetch<{ cidades: string[] }>(
          `/cidades.php?q=${encodeURIComponent(query)}&limit=25`
        );
        setSuggestions(data.cidades);
        setOpen(true);
      } catch {
        setSuggestions([]);
      }
    }, 280);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [query]);

  return (
    <div className="relative">
      <input
        className="sesmt-input"
        value={query}
        onChange={(e) => {
          setQuery(e.target.value);
          onChange(e.target.value);
        }}
        onFocus={() => suggestions.length > 0 && setOpen(true)}
        onBlur={() => setTimeout(() => setOpen(false), 150)}
        placeholder="Digite a cidade (MG)"
        required={required}
        autoComplete="off"
      />
      {open && suggestions.length > 0 && (
        <ul className="absolute z-20 mt-1 w-full max-h-48 overflow-auto bg-white border border-sesmt-forest/15 rounded-[10px] shadow-sesmt text-sm">
          {suggestions.map((cidade) => (
            <li key={cidade}>
              <button
                type="button"
                className="w-full text-left px-3 py-2 hover:bg-sesmt-accent-muted text-sesmt-forest"
                onMouseDown={(e) => e.preventDefault()}
                onClick={() => {
                  setQuery(cidade);
                  onChange(cidade);
                  setOpen(false);
                }}
              >
                {cidade}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
