"use client";

import { useTheme } from "next-themes";
import { useEffect, useState } from "react";
import { HiDownload, HiMail } from "react-icons/hi";
import { FaGamepad, FaMapMarkerAlt } from "react-icons/fa";
import { MdVerified } from "react-icons/md";
import { BsSunFill, BsMoonFill } from "react-icons/bs";

export default function Hero() {
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  return (
    <section className="pt-24 pb-12 px-4">
      <div className="max-w-6xl mx-auto flex flex-col items-center text-center">
        {/* Dark mode toggle */}
        <div className="self-end mb-6">
          {mounted && (
            <div className="flex items-center gap-1 bg-card-bg border border-card-border rounded-full p-1">
              <button
                onClick={() => setTheme("dark")}
                className={`p-2 rounded-full transition-colors ${
                  theme === "dark"
                    ? "bg-accent text-white"
                    : "text-muted hover:text-foreground"
                }`}
                aria-label="Dark mode"
              >
                <BsMoonFill size={14} />
              </button>
              <button
                onClick={() => setTheme("light")}
                className={`p-2 rounded-full transition-colors ${
                  theme === "light"
                    ? "bg-accent text-white"
                    : "text-muted hover:text-foreground"
                }`}
                aria-label="Light mode"
              >
                <BsSunFill size={14} />
              </button>
            </div>
          )}
        </div>

        {/* Profile image */}
        <div className="w-36 h-36 rounded-2xl overflow-hidden border-4 border-card-border mb-6 shadow-lg">
          <img
            src="/profile.jpg"
            alt="Christian Lanzaderas"
            className="w-full h-full object-cover"
          />
        </div>

        {/* Name + badge */}
        <h1 className="text-3xl sm:text-4xl font-mono font-bold flex items-center gap-2 mb-1">
         Christian Lanzaderas
          <MdVerified className="text-blue-500" size={24} />
        </h1>

        {/* Location */}
        <p className="text-muted flex items-center gap-1 text-sm mb-1">
          <FaMapMarkerAlt size={12} />
          Laguna, Philippines
        </p>

        {/* Title */}
        <p className="font-mono text-muted mb-6">Full-Stack Developer</p>

        {/* Action buttons */}
        <div className="flex flex-wrap justify-center gap-3">
          <a
            href="/resume.pdf"
            download
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-foreground text-background rounded-lg font-medium text-sm hover:opacity-90 transition-opacity"
          >
            <HiDownload size={16} />
            Download Resume
          </a>
          <a
            href="mailto:asnorsumdad@gmail.com"
            className="inline-flex items-center gap-2 px-5 py-2.5 border border-card-border bg-card-bg rounded-lg font-medium text-sm text-foreground hover:bg-tag-bg transition-colors"
          >
            <HiMail size={16} />
            Send Email
          </a>
          <a
            href="#games"
            className="inline-flex items-center gap-2 px-5 py-2.5 border border-card-border bg-card-bg rounded-lg font-medium text-sm text-foreground hover:bg-tag-bg transition-colors"
          >
            <FaGamepad size={16} />
            Play Games
          </a>
        </div>
      </div>
    </section>
  );
}