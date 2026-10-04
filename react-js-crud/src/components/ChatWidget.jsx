import { useEffect, useRef, useState } from "react";
import Api from "../api";

// ID sesi disimpan di browser agar riwayat (di Redis) menyambung
function sessionId() {
    let id = localStorage.getItem("chat_session");
    if (!id) {
        id = "s-" + Math.random().toString(36).slice(2) + Date.now().toString(36);
        localStorage.setItem("chat_session", id);
    }
    return id;
}

export default function ChatWidget() {
    const [buka, setBuka] = useState(false);
    const [pesan, setPesan] = useState("");
    const [riwayat, setRiwayat] = useState([]);
    const [memuat, setMemuat] = useState(false);
    const [n8n, setN8n] = useState(false);
    const ujung = useRef(null);

    useEffect(() => {
        if (buka) {
            Api.get(`/api/chat/${sessionId()}`).then((res) => {
                setRiwayat(res.data.history ?? []);
                setN8n(res.data.n8n ?? false);
            }).catch(() => {});
        }
    }, [buka]);

    useEffect(() => {
        ujung.current?.scrollIntoView({ behavior: "smooth" });
    }, [riwayat, memuat]);

    const kirim = async (e) => {
        e.preventDefault();
        const teks = pesan.trim();
        if (!teks || memuat) return;
        setPesan("");
        setRiwayat((r) => [...r, { role: "user", text: teks }]);
        setMemuat(true);
        try {
            const res = await Api.post("/api/chat", { session_id: sessionId(), message: teks });
            setRiwayat(res.data.history ?? []);
        } catch {
            setRiwayat((r) => [...r, { role: "bot", text: "Maaf, server tidak merespons. Coba lagi ya." }]);
        } finally {
            setMemuat(false);
        }
    };

    return (
        <>
            {/* Tombol gelembung */}
            <button
                onClick={() => setBuka(!buka)}
                className="btn btn-success rounded-circle shadow position-fixed"
                style={{ bottom: 24, right: 24, width: 58, height: 58, zIndex: 1050, fontSize: 24 }}
                title="Chat dengan asisten"
            >
                {buka ? "✕" : "💬"}
            </button>

            {/* Panel chat */}
            {buka && (
                <div className="card shadow-lg position-fixed d-flex flex-column"
                    style={{ bottom: 94, right: 24, width: 340, height: 440, zIndex: 1050, borderRadius: 16 }}>
                    <div className="card-header bg-success text-white d-flex justify-content-between align-items-center"
                        style={{ borderRadius: "16px 16px 0 0" }}>
                        <span className="fw-bold">🤖 Asisten Blog</span>
                        <small className="badge bg-light text-success">{n8n ? "n8n" : "bot bawaan"}</small>
                    </div>

                    <div className="flex-grow-1 overflow-auto p-3" style={{ background: "#f8f9fa" }}>
                        {riwayat.length === 0 && (
                            <div className="text-center text-muted small mt-4">
                                Halo! 👋 Tanyakan apa saja tentang blog ini.<br />
                                Misal: <em>"berapa post?"</em>
                            </div>
                        )}
                        {riwayat.map((m, i) => (
                            <div key={i} className={`d-flex mb-2 ${m.role === "user" ? "justify-content-end" : ""}`}>
                                <div className={`px-3 py-2 small ${m.role === "user"
                                        ? "bg-success text-white"
                                        : "bg-white border"}`}
                                    style={{ borderRadius: 14, maxWidth: "85%", whiteSpace: "pre-wrap" }}>
                                    {m.text}
                                </div>
                            </div>
                        ))}
                        {memuat && <div className="text-muted small">Mengetik…</div>}
                        <div ref={ujung} />
                    </div>

                    <form onSubmit={kirim} className="d-flex gap-2 p-2 border-top bg-white"
                        style={{ borderRadius: "0 0 16px 16px" }}>
                        <input
                            value={pesan}
                            onChange={(e) => setPesan(e.target.value)}
                            className="form-control form-control-sm"
                            placeholder="Tulis pesan..."
                            autoFocus
                        />
                        <button className="btn btn-success btn-sm px-3" disabled={memuat}>➤</button>
                    </form>
                </div>
            )}
        </>
    );
}
