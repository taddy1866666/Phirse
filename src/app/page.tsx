import Navigation from "@/components/Navigation";
import Hero from "@/components/Hero";
import About from "@/components/About";
import Skills from "@/components/Skills";
import SocialLinks from "@/components/SocialLinks";
import Projects from "@/components/Projects";
import Contact from "@/components/Contact";
import Footer from "@/components/Footer";

export default function Home() {
  return (
    <main className="min-h-screen bg-background">
      <Navigation />
      <Hero />

      {/* Two-column layout */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">
          {/* Left column */}
          <div className="space-y-6">
            <About />
            <Skills />
          </div>

          {/* Right column */}
          <div className="space-y-6">
            <SocialLinks />
          </div>
        </div>

        {/* Full width sections */}
        <div className="mt-8 space-y-6">
          <Projects />
          <Contact />
        </div>
      </div>

      <Footer />
    </main>
  );
}
