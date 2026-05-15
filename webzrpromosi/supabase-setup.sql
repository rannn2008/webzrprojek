create table if not exists public.menus (
    id uuid primary key default gen_random_uuid(),
    name text not null,
    price integer not null default 0,
    description text not null default '',
    image_url text not null default '',
    rating text not null default '4.8',
    badges text[] not null default '{}',
    status text not null default 'Tersedia',
    sort_order integer not null default 0,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

alter table public.menus enable row level security;

drop policy if exists "Public can read menus" on public.menus;
create policy "Public can read menus"
on public.menus
for select
to anon, authenticated
using (true);

drop policy if exists "Authenticated admins can insert menus" on public.menus;
create policy "Authenticated admins can insert menus"
on public.menus
for insert
to authenticated
with check (true);

drop policy if exists "Authenticated admins can update menus" on public.menus;
create policy "Authenticated admins can update menus"
on public.menus
for update
to authenticated
using (true)
with check (true);

drop policy if exists "Authenticated admins can delete menus" on public.menus;
create policy "Authenticated admins can delete menus"
on public.menus
for delete
to authenticated
using (true);

-- Catatan:
-- Jangan seed menu default dari file setup ini.
-- Menu asli dikelola dari admin dashboard supaya data menu yang sudah ada di Supabase tidak ketambahan menu bawaan.

-- ==========================================
-- TABEL REVIEWS (ULASAN PELANGGAN)
-- ==========================================
create table if not exists public.reviews (
    id uuid primary key default gen_random_uuid(),
    name text not null,
    rating integer not null check (rating >= 1 and rating <= 5),
    comment text not null,
    display_on_home boolean not null default false,
    created_at timestamptz not null default now()
);

alter table public.reviews
add column if not exists display_on_home boolean not null default false;

alter table public.reviews enable row level security;

drop policy if exists "Public can insert reviews" on public.reviews;
create policy "Public can insert reviews"
on public.reviews
for insert
to anon, authenticated
with check (display_on_home = false);

drop policy if exists "Public can read reviews" on public.reviews;
drop policy if exists "Public can read displayed reviews" on public.reviews;
create policy "Public can read displayed reviews"
on public.reviews
for select
to anon
using (display_on_home = true);

drop policy if exists "Authenticated admins can read reviews" on public.reviews;
create policy "Authenticated admins can read reviews"
on public.reviews
for select
to authenticated
using (true);

drop policy if exists "Authenticated admins can delete reviews" on public.reviews;
create policy "Authenticated admins can delete reviews"
on public.reviews
for delete
to authenticated
using (true);

drop policy if exists "Authenticated admins can update reviews" on public.reviews;
create policy "Authenticated admins can update reviews"
on public.reviews
for update
to authenticated
using (true)
with check (true);
