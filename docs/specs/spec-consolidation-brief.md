> **Deliverables produced from this brief:**
> - [`ink-consolidated-spec.md`](./ink-consolidated-spec.md)
> - [`ink-feature-list.md`](./ink-feature-list.md)
> - [`framework-recommendation.md`](./framework-recommendation.md)

---

Look at all the documents in ./docs. The idea was to do a lot of advance planning and then use Spec-kit to flesh it out and implement. But it appears Spec-Kit just gets confused when there is all this previously-prepared documentation.

I want you to go through all the documents in that folder and prepare a single spec under `./docs/specs` that structures all the existing information in a way that is suitable for ingestion to a system like Spec-kit or BMAD.

Include project purpose and principles, technology stack, architectural decisions, integration of the Lovable design elements already completed and the integration points of the various plugins.

Then, create a second document with an comprehensive feature list that may be fed to Spec-kit or BMAD.

Finally, make a recommendation of a spec framework (either Spec-kit or BMAD) that is best suited to build the site from the supplied specs.

Rules:
1. We are speccing the new site. Loads of background about the old site and waffling on about its needs is unhelpful and explicitly out of scope.
2. Be careful to ingest all the current documents before you start the rest of the work. I am not interested in specifications based on two or three documents that seemed applicable.
3. Do not invent new features that were not decided upon in the planning phase. You may ask about features that were discussed, if the decision is unclear or unknown.
4. Ask any clarifying questions you may need to. 