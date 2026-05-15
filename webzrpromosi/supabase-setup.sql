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

insert into public.menus (name, price, description, image_url, rating, badges, status, sort_order)
values
('Es Teller Original', 12000, 'Racikan klasik berisi buah segar, kuah manis, dan sensasi dingin yang pas.', 'foto2/estelleroriginal.jpg', '4.8', array['Best seller', 'Tersedia'], 'Tersedia', 1),
('Es Teller Alpukat Spesial', 16000, 'Menu unggulan dengan alpukat lebih banyak, creamy, dan bikin puas.', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=800&q=85', '4.9', array['Alpukat favorit', 'Tersedia'], 'Tersedia', 2),
('Es Teller Durian', 18000, 'Tambahan aroma durian untuk rasa yang lebih tebal dan nikmat.', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=800&q=85', '4.7', array['Aroma durian', 'Tersedia'], 'Tersedia', 3),
('Es Teller Jumbo', 20000, 'Porsi lebih besar untuk yang lagi haus berat dan butuh segar maksimal.', 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=800&q=85', '4.8', array['Porsi besar', 'Tersedia'], 'Tersedia', 4),
('Es Teller Campur', 15000, 'Campuran buah pilihan dengan rasa manis segar yang cocok untuk semua orang.', 'https://images.unsplash.com/photo-1502741224143-90386d7f8c82?auto=format&fit=crop&w=800&q=85', '4.7', array['Buah campur', 'Tersedia'], 'Tersedia', 5),
('Paket Keluarga', 55000, 'Paket ramai-ramai untuk keluarga, teman kantor, atau kumpul santai.', 'https://images.unsplash.com/photo-1551024506-0bccd828d307?auto=format&fit=crop&w=800&q=85', '4.9', array['Untuk ramai', 'Tersedia'], 'Tersedia', 6)
on conflict do nothing;

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
