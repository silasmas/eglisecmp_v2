import { Link } from 'react-router-dom';
import { Mail, MapPin, Phone } from 'lucide-react';
import { churchInfo } from '../../data/content';
import cmpLogo from '../../assets/Logo-CMP-2023-new.png';

function FacebookIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
      <path d="M13.5 21v-8.2h2.8l.4-3.2h-3.2V7.55c0-.93.26-1.56 1.59-1.56H16.8V3.13c-.3-.04-1.32-.13-2.5-.13-2.48 0-4.18 1.51-4.18 4.3v2.4H7.3v3.2h2.82V21h3.38Z" />
    </svg>
  );
}

function InstagramIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
      <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.35a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2ZM12 6.85A5.15 5.15 0 1 1 6.85 12 5.16 5.16 0 0 1 12 6.85Zm0 1.8A3.35 3.35 0 1 0 15.35 12 3.36 3.36 0 0 0 12 8.65Z" />
    </svg>
  );
}

function XIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
      <path d="M18.9 3H21l-6.54 7.47L22 21h-5.91l-4.62-6.03L6.2 21H4.1l6.99-7.98L2 3h6.06l4.18 5.54L18.9 3Zm-1.04 16.2h1.16L7.42 4.73H6.17L17.86 19.2Z" />
    </svg>
  );
}

function TikTokIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
      <path d="M14.42 3c.18 1.52 1.03 3 2.34 3.92.97.69 2.07 1 3.24 1.03v3.02a8.31 8.31 0 0 1-3.79-.9v5.38c0 3.3-2.6 5.83-5.9 5.83A5.88 5.88 0 0 1 7 10.1a5.9 5.9 0 0 1 3.22-.95v3.07a2.92 2.92 0 0 0-1.5.42 2.83 2.83 0 0 0 1.53 5.22 2.9 2.9 0 0 0 2.85-2.95V3h3.32Z" />
    </svg>
  );
}

function YouTubeIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
      <path d="M21.58 7.19a2.99 2.99 0 0 0-2.1-2.12C17.64 4.6 12 4.6 12 4.6s-5.64 0-7.48.47A2.99 2.99 0 0 0 2.42 7.2C1.95 9.05 1.95 12 1.95 12s0 2.95.47 4.8a2.99 2.99 0 0 0 2.1 2.12c1.84.47 7.48.47 7.48.47s5.64 0 7.48-.47a2.99 2.99 0 0 0 2.1-2.12c.47-1.84.47-4.8.47-4.8s0-2.95-.47-4.8ZM10.2 15.06V8.94L15.6 12l-5.4 3.06Z" />
    </svg>
  );
}

const footerLinks = [
  {
    title: 'Navigation',
    links: [
      { label: 'Accueil', href: '/' },
      { label: 'À propos', href: '/discover/about' },
      { label: 'Enseignements', href: '/teachings' },
      { label: 'Événements', href: '/events' },
      { label: 'Médias', href: '/media' },
    ],
  },
  {
    title: 'Nous rejoindre',
    links: [
      { label: 'Prendre rendez-vous', href: '/rendez-vous' },
      { label: 'Devenir membre', href: '/join' },
      { label: 'Demande de prière', href: '/requete-de-priere' },
      { label: 'Contact', href: '/contact' },
    ],
  },
  {
    title: 'Programmes',
    links: [
      { label: 'Culte dominical', href: '/events' },
      { label: 'Culte d\'enseignement', href: '/events' },
      { label: 'Bunda', href: '/events/bunda' },
      { label: 'Jeunesse', href: '/events' },
    ],
  },
];

export default function Footer() {
  return (
    <footer className="bg-surface-900 border-t border-surface-800">
      {/* Main footer */}
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
          {/* Brand */}
          <div className="lg:col-span-2">
            <Link to="/" className="flex items-center gap-3 mb-5">
              <img
                src={cmpLogo}
                alt="Logo Centre Missionnaire Philadelphie"
                className="h-12 w-12 shrink-0 object-contain brightness-0 invert"
              />
              <div className="leading-none">
                <div className="flex flex-col text-white">
                  <span className="font-heading text-[10px] font-semibold uppercase tracking-[0.16em] text-white/70">
                    {churchInfo.brandLineOne}
                  </span>
                  <span className="mt-1 font-heading text-[12px] font-bold tracking-[0.01em]">
                    {churchInfo.brandLineTwo}
                  </span>
                </div>
              </div>
            </Link>
            <p className="text-surface-400 text-[13px] leading-relaxed max-w-sm mb-6">
              {churchInfo.tagline}. Une maison de prière, de communion fraternelle et d'enseignement biblique au coeur de Kinshasa.
            </p>

            <div className="space-y-3 text-sm text-surface-400">
              <div className="flex items-start gap-3">
                <MapPin className="w-4 h-4 mt-0.5 text-burgundy-400 shrink-0" />
                <span>{churchInfo.address}</span>
              </div>
              <div className="flex items-center gap-3">
                <Phone className="w-4 h-4 text-burgundy-400 shrink-0" />
                <span>{churchInfo.phone}</span>
              </div>
              <div className="flex items-center gap-3">
                <Mail className="w-4 h-4 text-burgundy-400 shrink-0" />
                <span>{churchInfo.email}</span>
              </div>
              <div className="flex items-center gap-3">
                <span className="w-4 h-4 text-burgundy-400 shrink-0 text-[11px] font-semibold">W</span>
                <a href={churchInfo.website} target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">
                  {churchInfo.website.replace('https://', '')}
                </a>
              </div>
            </div>
          </div>

          {/* Link groups */}
          {footerLinks.map((group) => (
            <div key={group.title}>
              <h4 className="text-white font-semibold text-sm mb-4 uppercase tracking-wider">
                {group.title}
              </h4>
              <ul className="space-y-3">
                {group.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      to={link.href}
                      className="text-surface-400 hover:text-white text-sm transition-colors"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>

      {/* Bottom bar */}
      <div className="border-t border-surface-800">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-surface-500 text-xs">
            &copy; {new Date().getFullYear()} {churchInfo.name}. Tous droits réservés.
          </p>
          <div className="flex items-center gap-4">
            <a
              href={churchInfo.social.facebook}
              target="_blank"
              rel="noopener noreferrer"
              className="text-surface-500 hover:text-burgundy-400 transition-colors"
              aria-label="Facebook"
            >
              <FacebookIcon />
            </a>
            <a
              href={churchInfo.social.x}
              target="_blank"
              rel="noopener noreferrer"
              className="text-surface-500 hover:text-burgundy-400 transition-colors"
              aria-label="X"
            >
              <XIcon />
            </a>
            <a
              href={churchInfo.social.instagram}
              target="_blank"
              rel="noopener noreferrer"
              className="text-surface-500 hover:text-burgundy-400 transition-colors"
              aria-label="Instagram"
            >
              <InstagramIcon />
            </a>
            <a
              href={churchInfo.social.tiktok}
              target="_blank"
              rel="noopener noreferrer"
              className="text-surface-500 hover:text-burgundy-400 transition-colors"
              aria-label="TikTok"
            >
              <TikTokIcon />
            </a>
            <a
              href={churchInfo.social.youtube}
              target="_blank"
              rel="noopener noreferrer"
              className="text-surface-500 hover:text-burgundy-400 transition-colors"
              aria-label="YouTube"
            >
              <YouTubeIcon />
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}
