import { FaGithub, FaLinkedin, FaInstagram } from "react-icons/fa";
import { HiLink } from "react-icons/hi";

const socials = [
  {
    icon: <FaGithub size={20} />,
    label: "GitHub",
    href: "https://github.com/asnorsumdad",
  },
  {
    icon: <FaLinkedin size={20} />,
    label: "LinkedIn",
    href: "https://linkedin.com/in/asnorsumdad",
  },
  {
    icon: <FaInstagram size={20} />,
    label: "Instagram",
    href: "https://instagram.com/asnorsumdad",
  },
];

export default function SocialLinks() {
  return (
    <div className="bg-card-bg border border-card-border rounded-2xl p-6">
      <h2 className="text-lg font-bold flex items-center gap-2 mb-4">
        <HiLink className="text-muted" size={18} />
        Social Links
      </h2>

      <div className="space-y-3">
        {socials.map((social) => (
          <a
            key={social.label}
            href={social.href}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-3 text-sm text-muted hover:text-foreground transition-colors"
          >
            {social.icon}
            {social.label}
          </a>
        ))}
      </div>
    </div>
  );
}
