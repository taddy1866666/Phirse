import { HiCode } from "react-icons/hi";

const categories = [
  {
    title: "Web & Mobile Frontend",
    skills: ["TypeScript","Next.js", "Tailwind CSS",],
  },
  {
    title: "Backend & APIs",
    skills: ["Python","FastAPI", "PHP",],
  },
  {
    title: "Databases & Backend Services",
    skills: ["MySQL",],
  },
  {
    title: "Tools & Platforms",
    skills: ["Git", "GitHub", "Vercel", "Figma", "VS Code"],
  },
];

export default function Skills() {
  return (
    <section id="skills">
      <div className="bg-card-bg border border-card-border rounded-2xl p-6 sm:p-8">
        <h2 className="text-xl font-bold flex items-center gap-2 mb-6">
          <HiCode className="text-muted" size={20} />
          Tech Stack
        </h2>

        <div className="space-y-5">
          {categories.map((cat) => (
            <div key={cat.title}>
              <p className="text-sm text-muted mb-2">{cat.title}</p>
              <div className="flex flex-wrap gap-2">
                {cat.skills.map((skill) => (
                  <span
                    key={skill}
                    className="px-3 py-1.5 text-xs font-medium bg-tag-bg text-tag-text rounded-full border border-card-border"
                  >
                    {skill}
                  </span>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}