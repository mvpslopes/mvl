export type ClienteSite = {
  id: string;
  name: string;
  url: string;
  bg_color: string;
  sort_order: number;
  logo_url: string | null;
};

export type ClienteAdmin = ClienteSite & {
  logo: {
    original: string;
    stored: string;
    ext: string;
    size: number;
    path: string;
  } | null;
  updated_at: string;
};
