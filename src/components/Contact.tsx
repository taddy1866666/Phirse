"use client";

import { HiMail } from "react-icons/hi";

export default function Contact() {
  return (
    <section id="contact">
      <div className="bg-card-bg border border-card-border rounded-2xl p-6 sm:p-8">
        <h2 className="text-xl font-bold flex items-center gap-2 mb-6">
          <HiMail className="text-muted" size={20} />
          Get in Touch
        </h2>

        <form
          className="space-y-4"
          onSubmit={(e) => {
            e.preventDefault();
            const form = e.target as HTMLFormElement;
            const name = (form.elements.namedItem("name") as HTMLInputElement)
              .value;
            const email = (form.elements.namedItem("email") as HTMLInputElement)
              .value;
            const message = (
              form.elements.namedItem("message") as HTMLTextAreaElement
            ).value;
            window.location.href = `mailto:asnorsumdad@gmail.com?subject=Message from ${name}&body=${encodeURIComponent(
              message
            )}%0A%0AFrom: ${email}`;
          }}
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input
              name="name"
              type="text"
              placeholder="Your Name"
              required
              className="w-full px-4 py-2.5 rounded-lg bg-background border border-card-border text-sm text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent"
            />
            <input
              name="email"
              type="email"
              placeholder="Your Email"
              required
              className="w-full px-4 py-2.5 rounded-lg bg-background border border-card-border text-sm text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent"
            />
          </div>
          <textarea
            name="message"
            placeholder="Your Message"
            rows={4}
            required
            className="w-full px-4 py-2.5 rounded-lg bg-background border border-card-border text-sm text-foreground placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent resize-none"
          />
          <button
            type="submit"
            className="inline-flex items-center gap-2 px-6 py-2.5 bg-foreground text-background rounded-lg font-medium text-sm hover:opacity-90 transition-opacity"
          >
            <HiMail size={16} />
            Send Message
          </button>
        </form>
      </div>
    </section>
  );
}