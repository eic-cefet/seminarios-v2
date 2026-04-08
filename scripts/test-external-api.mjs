#!/usr/bin/env node

/**
 * Smoke test for the External API endpoints.
 *
 * Usage:
 *   node scripts/test-external-api.mjs <api-token> [base-url]
 *
 * Examples:
 *   node scripts/test-external-api.mjs sk-abc123
 *   node scripts/test-external-api.mjs sk-abc123 https://eic-seminarios.com
 */

const TOKEN = process.argv[2];
const BASE_URL = (process.argv[3] || "http://localhost:8000").replace(/\/$/, "");
const BASE = `${BASE_URL}/api/external/v1`;

if (!TOKEN) {
  console.error("Usage: node scripts/test-external-api.mjs <sk-token> [base-url]");
  process.exit(1);
}

let passed = 0;
let failed = 0;

async function api(method, path, body) {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: {
      Authorization: `Bearer ${TOKEN}`,
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await res.json().catch(() => null);
  return { status: res.status, data, ok: res.ok };
}

function assert(label, condition, detail) {
  if (condition) {
    console.log(`  ✓ ${label}`);
    passed++;
  } else {
    console.log(`  ✗ ${label}`);
    if (detail) console.log(`    →`, typeof detail === "string" ? detail : JSON.stringify(detail));
    failed++;
  }
}

console.log(`\nTesting against ${BASE}\n`);

// ───────────────────────────── Auth ──────────────────────────────

console.log("=== Auth ===\n");

const noAuth = await fetch(`${BASE}/seminars`, { headers: { Accept: "application/json" } });
assert("GET without token → 401", noAuth.status === 401);

const badAuth = await fetch(`${BASE}/seminars`, {
  headers: { Authorization: "Bearer invalid-token", Accept: "application/json" },
});
assert("GET with bad token → 401", badAuth.status === 401);

// ───────────────────────── Seminar Types ─────────────────────────

console.log("\n=== Seminar Types ===\n");

const typesList = await api("GET", "/seminar-types");
assert("GET /seminar-types → 200", typesList.status === 200);
assert("returns array", Array.isArray(typesList.data?.data));

const typeName = `Test Type ${Date.now()}`;
const typeCreate = await api("POST", "/seminar-types", { name: typeName });
assert("POST /seminar-types → 201", typeCreate.status === 201, typeCreate.data);
const typeId = typeCreate.data?.data?.id;
assert("returns id and name", typeId && typeCreate.data?.data?.name === typeName);

const typeDup = await api("POST", "/seminar-types", { name: typeName });
assert("POST duplicate → 422", typeDup.status === 422);

if (typeId) {
  const typeShow = await api("GET", `/seminar-types/${typeId}`);
  assert("GET /seminar-types/:id → 200", typeShow.status === 200);
  assert("returns correct type", typeShow.data?.data?.id === typeId);

  const updatedName = `Updated Type ${Date.now()}`;
  const typeUpdate = await api("PUT", `/seminar-types/${typeId}`, { name: updatedName });
  assert("PUT /seminar-types/:id → 200", typeUpdate.status === 200);
  assert("name updated", typeUpdate.data?.data?.name === updatedName);
}

const typeNotFound = await api("GET", "/seminar-types/999999");
assert("GET non-existent → 404", typeNotFound.status === 404);

// ───────────────────────── Locations ─────────────────────────────

console.log("\n=== Seminar Locations ===\n");

const locList = await api("GET", "/locations");
assert("GET /locations → 200", locList.status === 200);
assert("returns array", Array.isArray(locList.data?.data));

const locName = `Test Room ${Date.now()}`;
const locCreate = await api("POST", "/locations", { name: locName, max_vacancies: 42 });
assert("POST /locations → 201", locCreate.status === 201, locCreate.data);
const locId = locCreate.data?.data?.id;
assert("returns id, name, max_vacancies", locId && locCreate.data?.data?.max_vacancies === 42);

const locDup = await api("POST", "/locations", { name: locName, max_vacancies: 10 });
assert("POST duplicate → 422", locDup.status === 422);

if (locId) {
  const locShow = await api("GET", `/locations/${locId}`);
  assert("GET /locations/:id → 200", locShow.status === 200);
  assert("returns correct location", locShow.data?.data?.id === locId);

  const locUpdate = await api("PUT", `/locations/${locId}`, { max_vacancies: 100 });
  assert("PUT /locations/:id → 200", locUpdate.status === 200);
  assert("max_vacancies updated", locUpdate.data?.data?.max_vacancies === 100);
  assert("name unchanged", locUpdate.data?.data?.name === locName);
}

const locBadCreate = await api("POST", "/locations", { name: "X" });
assert("POST missing max_vacancies → 422", locBadCreate.status === 422);

// ─────────────────────────── Users ───────────────────────────────

console.log("\n=== Users ===\n");

const usersList = await api("GET", "/users");
assert("GET /users → 200", usersList.status === 200);
assert("returns paginated data", Array.isArray(usersList.data?.data));

const userEmail = `smoke-${Date.now()}@test.local`;
const userCreate = await api("POST", "/users", {
  name: "Smoke Test User",
  email: userEmail,
  username: `smoke${Date.now()}`,
});
assert("POST /users → 201", userCreate.status === 201, userCreate.data);
const userId = userCreate.data?.data?.id;
assert("returns id, name, email, username", userId && userCreate.data?.data?.email === userEmail);
assert("speaker_data is null initially", userCreate.data?.data?.speaker_data === null);

const userDup = await api("POST", "/users", { name: "Dup", email: userEmail });
assert("POST duplicate email → 422", userDup.status === 422);

if (userId) {
  const userShow = await api("GET", `/users/${userId}`);
  assert("GET /users/:id → 200", userShow.status === 200);
  assert("returns correct user", userShow.data?.data?.id === userId);

  const userUpdate = await api("PUT", `/users/${userId}`, { name: "Updated User" });
  assert("PUT /users/:id → 200", userUpdate.status === 200);
  assert("name updated", userUpdate.data?.data?.name === "Updated User");

  // Search by name
  const searchName = await api("GET", "/users?search=Updated+User");
  assert("GET /users?search= finds user", searchName.data?.data?.some((u) => u.id === userId));

  // Search by email
  const searchEmail = await api("GET", `/users?email=${userEmail}`);
  assert("GET /users?email= finds exact match", searchEmail.data?.data?.length === 1);
}

const userNoPassword = await api("POST", "/users", {
  name: "No Pass",
  email: `nopass-${Date.now()}@test.local`,
  password: "should-be-ignored",
});
assert("POST ignores password field", userNoPassword.status === 201);

// ──────────────────────── Speaker Data ───────────────────────────

console.log("\n=== Speaker Data ===\n");

if (userId) {
  const sdShow = await api("GET", `/users/${userId}/speaker-data`);
  assert("GET /users/:id/speaker-data → 200", sdShow.status === 200);
  assert("initially null", sdShow.data?.data === null);

  const sdCreate = await api("PUT", `/users/${userId}/speaker-data`, {
    institution: "CEFET-RJ",
    description: "Professor",
  });
  assert("PUT creates speaker data → 200", sdCreate.status === 200, sdCreate.data);
  assert("institution set", sdCreate.data?.data?.institution === "CEFET-RJ");
  assert("description set", sdCreate.data?.data?.description === "Professor");
  assert("slug auto-generated", typeof sdCreate.data?.data?.slug === "string" && sdCreate.data.data.slug.length > 0);

  const sdUpdate = await api("PUT", `/users/${userId}/speaker-data`, {
    institution: "MIT",
  });
  assert("PUT updates speaker data", sdUpdate.data?.data?.institution === "MIT");

  const sdClear = await api("PUT", `/users/${userId}/speaker-data`, {
    institution: null,
  });
  assert("PUT clears field with null", sdClear.data?.data?.institution === null);

  // Verify user show includes speaker_data
  const userWithSd = await api("GET", `/users/${userId}`);
  assert("GET user includes speaker_data", userWithSd.data?.data?.speaker_data !== null);
}

// ─────────────────────────── Seminars ────────────────────────────

console.log("\n=== Seminars ===\n");

const semCreate = await api("POST", "/seminars", {
  name: `Smoke Seminar ${Date.now()}`,
  description: "Automated smoke test",
  scheduled_at: "2099-12-31T14:00:00",
  active: true,
  seminar_location_id: locId,
  seminar_type_id: typeId,
  subjects: ["Smoke Testing", "API Integration"],
  speaker_ids: [userId],
});
assert("POST /seminars → 201", semCreate.status === 201, semCreate.data);

const semId = semCreate.data?.data?.id;
const sem = semCreate.data?.data;

if (sem) {
  assert("has slug", typeof sem.slug === "string" && sem.slug.length > 0);
  assert("has location object", sem.location?.id === locId);
  assert("has seminar_type", typeof sem.seminar_type === "string");
  assert("subjects is string[]", Array.isArray(sem.subjects) && sem.subjects.length === 2);
  assert("speakers is object[]", Array.isArray(sem.speakers) && sem.speakers.length === 1);
  assert("speaker has id, name, email", sem.speakers[0]?.id === userId && sem.speakers[0]?.email);
}

// List
const semList = await api("GET", "/seminars");
assert("GET /seminars → 200", semList.status === 200);
assert("returns paginated data", Array.isArray(semList.data?.data));

// Search
const semSearch = await api("GET", "/seminars?search=Smoke+Seminar");
assert("GET /seminars?search= finds results", semSearch.data?.data?.length >= 1);

// Filter active
const semActive = await api("GET", "/seminars?active=true");
assert("GET /seminars?active=true → 200", semActive.status === 200);

// Show
if (semId) {
  const semShow = await api("GET", `/seminars/${semId}`);
  assert("GET /seminars/:id → 200", semShow.status === 200);
  assert("returns correct seminar", semShow.data?.data?.id === semId);

  // Update name
  const semUpdateName = await api("PUT", `/seminars/${semId}`, {
    name: `Updated Smoke ${Date.now()}`,
  });
  assert("PUT name → 200", semUpdateName.status === 200);
  assert("name updated", semUpdateName.data?.data?.name?.startsWith("Updated"));
  assert("slug regenerated", semUpdateName.data?.data?.slug?.includes("updated"));

  // Update active
  const semDeactivate = await api("PUT", `/seminars/${semId}`, { active: false });
  assert("PUT active=false", semDeactivate.data?.data?.active === false);

  // Update speaker_ids
  const newUser = await api("POST", "/users", {
    name: "Second Speaker",
    email: `second-${Date.now()}@test.local`,
  });
  if (newUser.data?.data?.id) {
    const semUpdateSpeakers = await api("PUT", `/seminars/${semId}`, {
      speaker_ids: [newUser.data.data.id],
    });
    assert("PUT speaker_ids replaces speakers", semUpdateSpeakers.data?.data?.speakers?.length === 1);
    assert("new speaker set", semUpdateSpeakers.data?.data?.speakers?.[0]?.id === newUser.data.data.id);
  }

  // Update subjects
  const semUpdateSubjects = await api("PUT", `/seminars/${semId}`, {
    subjects: ["New Subject"],
  });
  assert("PUT subjects replaces", semUpdateSubjects.data?.data?.subjects?.length === 1);
  assert("new subject set", semUpdateSubjects.data?.data?.subjects?.[0] === "New Subject");

  // Update location
  const newLoc = await api("POST", "/locations", { name: `New Loc ${Date.now()}`, max_vacancies: 200 });
  if (newLoc.data?.data?.id) {
    const semUpdateLoc = await api("PUT", `/seminars/${semId}`, {
      seminar_location_id: newLoc.data.data.id,
    });
    assert("PUT seminar_location_id", semUpdateLoc.data?.data?.location?.id === newLoc.data.data.id);
  }

  // Clear seminar type
  const semClearType = await api("PUT", `/seminars/${semId}`, { seminar_type_id: null });
  assert("PUT seminar_type_id=null", semClearType.data?.data?.seminar_type === null);
}

// ─────────────────────── Validation ──────────────────────────────

console.log("\n=== Validation ===\n");

const badSeminar = await api("POST", "/seminars", {});
assert("POST /seminars empty → 422", badSeminar.status === 422);

const badSpeakerIds = await api("POST", "/seminars", {
  name: "Test",
  scheduled_at: "2099-01-01T00:00:00",
  seminar_location_id: locId,
  subjects: ["Test"],
  speaker_ids: [999999],
});
assert("POST invalid speaker_ids → 422", badSpeakerIds.status === 422);

const badLocId = await api("POST", "/seminars", {
  name: "Test",
  scheduled_at: "2099-01-01T00:00:00",
  seminar_location_id: 999999,
  subjects: ["Test"],
  speaker_ids: [userId],
});
assert("POST invalid seminar_location_id → 422", badLocId.status === 422);

const badTypeId = await api("POST", "/seminars", {
  name: "Test",
  scheduled_at: "2099-01-01T00:00:00",
  seminar_location_id: locId,
  seminar_type_id: 999999,
  subjects: ["Test"],
  speaker_ids: [userId],
});
assert("POST invalid seminar_type_id → 422", badTypeId.status === 422);

const badUser = await api("POST", "/users", {});
assert("POST /users empty → 422", badUser.status === 422);

const badLoc = await api("POST", "/locations", {});
assert("POST /locations empty → 422", badLoc.status === 422);

const badType = await api("POST", "/seminar-types", {});
assert("POST /seminar-types empty → 422", badType.status === 422);

// ─────────────────────────────────────────────────────────────────

console.log(`\n${"═".repeat(50)}`);
console.log(`  Results: ${passed} passed, ${failed} failed`);
console.log(`${"═".repeat(50)}\n`);

process.exit(failed > 0 ? 1 : 0);
