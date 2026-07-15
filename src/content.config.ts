import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const termine = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/termine' }),
  schema: z.object({
    titel: z.string(),
    datum: z.coerce.date(),
    uhrzeit: z.string().optional(),
    ort: z.string().default('Mendig, Laacher-See-Halle'),
    kategorie: z.enum(['sitzung', 'umzug', 'party', 'verein', 'sonstiges']).default('sonstiges'),
    ticketlink: z.string().url().optional(),
    beschreibung: z.string().optional(),
  }),
});

const vorstand = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/vorstand' }),
  schema: z.object({
    name: z.string(),
    funktion: z.string(),
    korporation: z.string().optional(),
    email: z.string().optional(),
    foto: z.string().optional(),
    reihenfolge: z.number().default(99),
  }),
});

const korporationen = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/korporationen' }),
  schema: z.object({
    name: z.string(),
    kurzname: z.string().optional(),
    claim: z.string().optional(),
    farbe: z.string().default('#d40000'),
    logo: z.string().optional(),
    bild: z.string().optional(),
    externe_url: z.string().url().optional(),
    instagram: z.string().optional(),
    ansprechpartner: z.string().optional(),
    email: z.string().optional(),
    reihenfolge: z.number().default(99),
  }),
});

const sponsoren = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/sponsoren' }),
  schema: z.object({
    name: z.string(),
    logo: z.string().optional(),
    url: z.string().url().optional(),
  }),
});

const news = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/news' }),
  schema: z.object({
    titel: z.string(),
    datum: z.coerce.date(),
    teaser: z.string().optional(),
  }),
});

export const collections = { termine, vorstand, korporationen, sponsoren, news };
