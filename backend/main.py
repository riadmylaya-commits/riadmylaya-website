import asyncio
import base64
import io
import logging
import os
import secrets
import uuid
from datetime import datetime, timedelta, timezone
from email.message import EmailMessage
from pathlib import Path

import aiosmtplib
import bcrypt
from fastapi import Depends, FastAPI, HTTPException, Query, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from fastapi.staticfiles import StaticFiles
from fpdf import FPDF
from jose import JWTError, jwt
from pydantic import BaseModel
from pydantic_settings import BaseSettings
from sqlalchemy import Boolean, Column, DateTime, Integer, String, Text, create_engine, desc
from sqlalchemy.orm import DeclarativeBase, Session, sessionmaker

# ─── Config ──────────────────────────────────────────────────────────────────

class Settings(BaseSettings):
    DATABASE_URL: str = os.getenv("DATABASE_URL", "sqlite:////data/riadmylaya.db" if os.path.isdir("/data") else "sqlite:///./riadmylaya.db")
    SECRET_KEY: str = os.getenv("SECRET_KEY", "riadmylaya-secret-key-change-in-production-2024")
    ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 480
    GMAIL_EMAIL: str = "riadmylaya@gmail.com"
    GMAIL_APP_PASSWORD: str = os.getenv("GMAIL_APP_PASSWORD", "")
    NOTIFICATION_EMAIL: str = "riadmylaya@gmail.com"
    FRONTEND_URL: str = os.getenv("FRONTEND_URL", "")
    PASSWORD_RESET_EXPIRE_MINUTES: int = 30
    model_config = {"env_file": ".env", "extra": "ignore"}

settings = Settings()

# ─── Database ────────────────────────────────────────────────────────────────

connect_args = {"check_same_thread": False} if settings.DATABASE_URL.startswith("sqlite") else {}
engine = create_engine(settings.DATABASE_URL, connect_args=connect_args)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

class Base(DeclarativeBase):
    pass

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

def _uuid() -> str:
    return str(uuid.uuid4())

# ─── Models ──────────────────────────────────────────────────────────────────

class StaffUser(Base):
    __tablename__ = "staff_users"
    id = Column(String, primary_key=True, default=_uuid)
    username = Column(String, unique=True, nullable=False, index=True)
    email = Column(String, unique=True, nullable=False)
    hashed_password = Column(String, nullable=False)
    is_admin = Column(Boolean, default=False)
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

class Registration(Base):
    __tablename__ = "registrations"
    id = Column(String, primary_key=True, default=_uuid)
    room = Column(String, nullable=False)
    last_name = Column(String, nullable=False)
    first_name = Column(String, nullable=False)
    date_of_birth = Column(String, nullable=False)
    place_of_birth = Column(String, nullable=False)
    nationality = Column(String, nullable=False)
    occupation = Column(String, nullable=False)
    cin_number = Column(String, nullable=False)
    morocco_entry_number = Column(String, nullable=False)
    arrival_date = Column(String, nullable=False)
    departure_date = Column(String, nullable=False)
    accompanying_children = Column(Integer, default=0)
    coming_from = Column(String, nullable=False)
    going_to = Column(String, nullable=False)
    passport_number = Column(String, nullable=False)
    passport_issue_date = Column(String, nullable=False)
    passport_issue_place = Column(String, nullable=False)
    permanent_address = Column(String, nullable=False)
    passport_photo = Column(Text, nullable=True)
    signature = Column(Text, nullable=True)
    registration_date = Column(String, nullable=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

class PasswordResetToken(Base):
    __tablename__ = "password_reset_tokens"
    id = Column(String, primary_key=True, default=_uuid)
    user_id = Column(String, nullable=False, index=True)
    token = Column(String, unique=True, nullable=False, index=True)
    expires_at = Column(DateTime, nullable=False)
    used = Column(Boolean, default=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

# ─── Schemas ─────────────────────────────────────────────────────────────────

class RegistrationCreate(BaseModel):
    room: str
    lastName: str
    firstName: str
    dateOfBirth: str
    placeOfBirth: str
    nationality: str
    occupation: str
    cinNumber: str
    moroccoEntryNumber: str
    arrivalDate: str
    departureDate: str
    accompanyingChildren: int = 0
    comingFrom: str
    goingTo: str
    passportNumber: str
    passportIssueDate: str
    passportIssuePlace: str
    permanentAddress: str
    passportPhoto: str = ""
    signature: str = ""
    registrationDate: str = ""

class RegistrationResponse(BaseModel):
    id: str
    room: str
    lastName: str
    firstName: str
    dateOfBirth: str
    placeOfBirth: str
    nationality: str
    occupation: str
    cinNumber: str
    moroccoEntryNumber: str
    arrivalDate: str
    departureDate: str
    accompanyingChildren: int
    comingFrom: str
    goingTo: str
    passportNumber: str
    passportIssueDate: str
    passportIssuePlace: str
    permanentAddress: str
    passportPhoto: str
    signature: str
    registrationDate: str
    createdAt: str
    model_config = {"from_attributes": True}

class StaffLogin(BaseModel):
    username: str
    password: str

class StaffCreateSchema(BaseModel):
    username: str
    email: str
    password: str
    is_admin: bool = False

class StaffResponse(BaseModel):
    id: str
    username: str
    email: str
    is_admin: bool
    is_active: bool
    model_config = {"from_attributes": True}

class ChangePassword(BaseModel):
    current_password: str
    new_password: str

class ForgotPassword(BaseModel):
    email: str

class ResetPasswordSchema(BaseModel):
    token: str
    new_password: str

class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    user: StaffResponse

# ─── Auth helpers ────────────────────────────────────────────────────────────

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)
security = HTTPBearer()

def hash_password(pw: str) -> str:
    return bcrypt.hashpw(pw.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")

def verify_password(plain: str, hashed: str) -> bool:
    return bcrypt.checkpw(plain.encode("utf-8"), hashed.encode("utf-8"))

def create_access_token(data: dict) -> str:
    to_encode = data.copy()
    to_encode["exp"] = datetime.now(timezone.utc) + timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)
    return jwt.encode(to_encode, settings.SECRET_KEY, algorithm=settings.ALGORITHM)

def get_current_user(
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db),
) -> StaffUser:
    exc = HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Could not validate credentials")
    try:
        payload = jwt.decode(credentials.credentials, settings.SECRET_KEY, algorithms=[settings.ALGORITHM])
        user_id = payload.get("sub", "")
        if not user_id:
            raise exc
    except JWTError:
        raise exc
    user = db.query(StaffUser).filter(StaffUser.id == user_id).first()
    if not user or not user.is_active:
        raise exc
    return user

def get_admin_user(current_user: StaffUser = Depends(get_current_user)) -> StaffUser:
    if not current_user.is_admin:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Admin access required")
    return current_user

# ─── Email service ───────────────────────────────────────────────────────────

def _decode_base64_image(data_url: str) -> bytes:
    if "," in data_url:
        data_url = data_url.split(",", 1)[1]
    return base64.b64decode(data_url)

def generate_registration_pdf(reg: dict) -> bytes:
    pdf = FPDF()
    pdf.add_page()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.set_font("Helvetica", "B", 18)
    pdf.cell(0, 12, "Riad Mylaya", new_x="LMARGIN", new_y="NEXT", align="C")
    pdf.set_font("Helvetica", "", 10)
    pdf.cell(0, 6, "163, Derb Boumba, Medina, Marrakech", new_x="LMARGIN", new_y="NEXT", align="C")
    pdf.ln(4)
    pdf.set_font("Helvetica", "B", 14)
    pdf.cell(0, 10, "Fiche de Police / Guest Registration", new_x="LMARGIN", new_y="NEXT", align="C")
    pdf.ln(6)
    pdf.set_draw_color(200, 180, 150)
    pdf.line(10, pdf.get_y(), 200, pdf.get_y())
    pdf.ln(4)

    fields = [
        ("Chambre / Room", reg.get("room", "")),
        ("Nom / Surname", reg.get("lastName", "")),
        ("Prenom / First name", reg.get("firstName", "")),
        ("Date de naissance / Date of birth", reg.get("dateOfBirth", "")),
        ("Lieu de naissance / Place of birth", reg.get("placeOfBirth", "")),
        ("Nationalite / Nationality", reg.get("nationality", "")),
        ("Profession / Occupation", reg.get("occupation", "")),
        ("N. C.I.N / ID card", reg.get("cinNumber", "")),
        ("N. d'entree au Maroc / Morocco entry", reg.get("moroccoEntryNumber", "")),
        ("Date d'arrivee / Arrival", reg.get("arrivalDate", "")),
        ("Date de depart / Departure", reg.get("departureDate", "")),
        ("Mineurs accompagnants / Children", str(reg.get("accompanyingChildren", 0))),
        ("Lieu de provenance / Coming from", reg.get("comingFrom", "")),
        ("Destination / Going to", reg.get("goingTo", "")),
        ("N. Passeport / Passport number", reg.get("passportNumber", "")),
        ("Date de delivrance / Issue date", reg.get("passportIssueDate", "")),
        ("Lieu de delivrance / Issue place", reg.get("passportIssuePlace", "")),
        ("Adresse actuelle / Address", reg.get("permanentAddress", "")),
        ("Date / Marrakech, le", reg.get("registrationDate", "")),
    ]
    for label, value in fields:
        pdf.set_font("Helvetica", "B", 9)
        pdf.cell(80, 7, label, border=0)
        pdf.set_font("Helvetica", "", 9)
        pdf.cell(0, 7, str(value), border=0, new_x="LMARGIN", new_y="NEXT")

    passport_photo = reg.get("passportPhoto", "")
    if passport_photo and "base64" in passport_photo:
        try:
            img_bytes = _decode_base64_image(passport_photo)
            pdf.ln(6)
            pdf.set_font("Helvetica", "B", 10)
            pdf.cell(0, 8, "Photo du passeport / Passport photo:", new_x="LMARGIN", new_y="NEXT")
            pdf.image(io.BytesIO(img_bytes), x=10, w=50)
        except Exception as e:
            logger.warning("Could not embed passport photo in PDF: %s", e)

    signature = reg.get("signature", "")
    if signature and "base64" in signature:
        try:
            sig_bytes = _decode_base64_image(signature)
            pdf.ln(6)
            pdf.set_font("Helvetica", "B", 10)
            pdf.cell(0, 8, "Signature:", new_x="LMARGIN", new_y="NEXT")
            pdf.image(io.BytesIO(sig_bytes), x=10, w=60)
        except Exception as e:
            logger.warning("Could not embed signature in PDF: %s", e)

    return bytes(pdf.output())

async def send_registration_email(reg: dict) -> bool:
    if not settings.GMAIL_APP_PASSWORD:
        logger.warning("GMAIL_APP_PASSWORD not set, skipping email")
        return False
    try:
        pdf_bytes = generate_registration_pdf(reg)
        msg = EmailMessage()
        msg["From"] = settings.GMAIL_EMAIL
        msg["To"] = settings.NOTIFICATION_EMAIL
        msg["Subject"] = (
            f"Nouvelle fiche - {reg.get('firstName', '')} {reg.get('lastName', '')} "
            f"| Chambre {reg.get('room', '')} | {reg.get('arrivalDate', '')} - {reg.get('departureDate', '')}"
        )
        html_body = f"""<html><body style="font-family:Arial,sans-serif;color:#3b1a10;background:#faf7f2;padding:20px;">
<div style="max-width:600px;margin:0 auto;background:white;border-radius:12px;padding:24px;border:1px solid #ebe0cc;">
<h2 style="color:#5a2d1e;text-align:center;">Riad Mylaya — Nouvelle Fiche Client</h2><hr style="border-color:#ebe0cc;">
<table style="width:100%;border-collapse:collapse;">
<tr><td style="padding:6px;font-weight:bold;width:40%;">Chambre</td><td style="padding:6px;">{reg.get('room','')}</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Nom</td><td style="padding:6px;">{reg.get('lastName','')}</td></tr>
<tr><td style="padding:6px;font-weight:bold;">Pr&eacute;nom</td><td style="padding:6px;">{reg.get('firstName','')}</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Arriv&eacute;e</td><td style="padding:6px;">{reg.get('arrivalDate','')}</td></tr>
<tr><td style="padding:6px;font-weight:bold;">D&eacute;part</td><td style="padding:6px;">{reg.get('departureDate','')}</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Nationalit&eacute;</td><td style="padding:6px;">{reg.get('nationality','')}</td></tr>
<tr><td style="padding:6px;font-weight:bold;">N&deg; Passeport</td><td style="padding:6px;">{reg.get('passportNumber','')}</td></tr>
</table><hr style="border-color:#ebe0cc;">
<p style="text-align:center;color:#6b3a2a;font-size:12px;">La fiche compl&egrave;te en PDF est jointe. Photo passeport et signature incluses.</p>
</div></body></html>"""
        msg.set_content("Nouvelle fiche client — voir version HTML ou PDF joint.")
        msg.add_alternative(html_body, subtype="html")
        msg.add_attachment(pdf_bytes, maintype="application", subtype="pdf",
            filename=f"fiche_{reg.get('lastName','client')}_{reg.get('firstName','')}_{reg.get('arrivalDate','')}.pdf")
        pp = reg.get("passportPhoto", "")
        if pp and "base64" in pp:
            try:
                ext = "jpg" if "image/jpeg" in pp else "png"
                msg.add_attachment(_decode_base64_image(pp), maintype="image", subtype=ext,
                    filename=f"passeport_{reg.get('lastName','client')}.{ext}")
            except Exception:
                pass
        sig = reg.get("signature", "")
        if sig and "base64" in sig:
            try:
                msg.add_attachment(_decode_base64_image(sig), maintype="image", subtype="png",
                    filename=f"signature_{reg.get('lastName','client')}.png")
            except Exception:
                pass
        await aiosmtplib.send(msg, hostname="smtp.gmail.com", port=587, start_tls=True,
            username=settings.GMAIL_EMAIL, password=settings.GMAIL_APP_PASSWORD)
        logger.info("Email sent for %s %s", reg.get("firstName"), reg.get("lastName"))
        return True
    except Exception as e:
        logger.error("Failed to send email: %s", e)
        return False

async def send_password_reset_email(email: str, username: str, reset_url: str) -> bool:
    if not settings.GMAIL_APP_PASSWORD:
        return False
    try:
        msg = EmailMessage()
        msg["From"] = settings.GMAIL_EMAIL
        msg["To"] = email
        msg["Subject"] = "Riad Mylaya — Réinitialisation du mot de passe"
        html = f"""<html><body style="font-family:Arial,sans-serif;color:#3b1a10;background:#faf7f2;padding:20px;">
<div style="max-width:500px;margin:0 auto;background:white;border-radius:12px;padding:24px;border:1px solid #ebe0cc;">
<h2 style="color:#5a2d1e;text-align:center;">Riad Mylaya</h2>
<p>Bonjour <strong>{username}</strong>,</p>
<p>Vous avez demand&eacute; la r&eacute;initialisation de votre mot de passe.</p>
<p style="text-align:center;"><a href="{reset_url}" style="display:inline-block;background:#5a2d1e;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">R&eacute;initialiser mon mot de passe</a></p>
<p style="font-size:12px;color:#888;">Ce lien expire dans 30 minutes.</p>
</div></body></html>"""
        msg.set_content(f"Réinitialisez votre mot de passe: {reset_url}")
        msg.add_alternative(html, subtype="html")
        await aiosmtplib.send(msg, hostname="smtp.gmail.com", port=587, start_tls=True,
            username=settings.GMAIL_EMAIL, password=settings.GMAIL_APP_PASSWORD)
        return True
    except Exception as e:
        logger.error("Failed to send reset email: %s", e)
        return False

# ─── FastAPI app ─────────────────────────────────────────────────────────────

app = FastAPI(title="Riad Mylaya API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
def on_startup():
    Base.metadata.create_all(bind=engine)
    db = SessionLocal()
    try:
        if not db.query(StaffUser).filter(StaffUser.username == "admin").first():
            db.add(StaffUser(username="admin", email=settings.NOTIFICATION_EMAIL,
                hashed_password=hash_password("mylaya2024"), is_admin=True))
            db.commit()
            logger.info("Default admin created (username: admin)")
    finally:
        db.close()

@app.get("/api/health")
def health():
    return {"status": "ok"}

# ─── Registration helper ────────────────────────────────────────────────────

def _reg_to_response(r: Registration) -> RegistrationResponse:
    return RegistrationResponse(
        id=r.id, room=r.room, lastName=r.last_name, firstName=r.first_name,
        dateOfBirth=r.date_of_birth, placeOfBirth=r.place_of_birth,
        nationality=r.nationality, occupation=r.occupation,
        cinNumber=r.cin_number, moroccoEntryNumber=r.morocco_entry_number,
        arrivalDate=r.arrival_date, departureDate=r.departure_date,
        accompanyingChildren=r.accompanying_children,
        comingFrom=r.coming_from, goingTo=r.going_to,
        passportNumber=r.passport_number, passportIssueDate=r.passport_issue_date,
        passportIssuePlace=r.passport_issue_place,
        permanentAddress=r.permanent_address,
        passportPhoto=r.passport_photo or "", signature=r.signature or "",
        registrationDate=r.registration_date,
        createdAt=r.created_at.isoformat() if r.created_at else "",
    )

# ─── Registration routes ────────────────────────────────────────────────────

@app.post("/api/registrations", response_model=RegistrationResponse)
async def create_registration(data: RegistrationCreate, db: Session = Depends(get_db)):
    reg = Registration(
        room=data.room, last_name=data.lastName, first_name=data.firstName,
        date_of_birth=data.dateOfBirth, place_of_birth=data.placeOfBirth,
        nationality=data.nationality, occupation=data.occupation,
        cin_number=data.cinNumber, morocco_entry_number=data.moroccoEntryNumber,
        arrival_date=data.arrivalDate, departure_date=data.departureDate,
        accompanying_children=data.accompanyingChildren,
        coming_from=data.comingFrom, going_to=data.goingTo,
        passport_number=data.passportNumber, passport_issue_date=data.passportIssueDate,
        passport_issue_place=data.passportIssuePlace,
        permanent_address=data.permanentAddress,
        passport_photo=data.passportPhoto, signature=data.signature,
        registration_date=data.registrationDate,
    )
    db.add(reg)
    db.commit()
    db.refresh(reg)
    reg_dict = data.model_dump()
    reg_dict["id"] = reg.id
    asyncio.create_task(send_registration_email(reg_dict))
    return _reg_to_response(reg)

@app.get("/api/registrations", response_model=list[RegistrationResponse])
def list_registrations(
    search: str = Query(""), date: str = Query(""),
    db: Session = Depends(get_db), _u: StaffUser = Depends(get_current_user),
):
    q = db.query(Registration)
    if search:
        like = f"%{search.lower()}%"
        q = q.filter((Registration.first_name.ilike(like)) | (Registration.last_name.ilike(like)))
    if date:
        q = q.filter(Registration.arrival_date == date)
    return [_reg_to_response(r) for r in q.order_by(desc(Registration.created_at)).all()]

@app.get("/api/registrations/stats/today")
def registration_stats(db: Session = Depends(get_db), _u: StaffUser = Depends(get_current_user)):
    today = datetime.now(timezone.utc).strftime("%Y-%m-%d")
    return {
        "todayArrivals": db.query(Registration).filter(Registration.arrival_date == today).count(),
        "totalRegistrations": db.query(Registration).count(),
    }

@app.get("/api/registrations/{rid}", response_model=RegistrationResponse)
def get_registration(rid: str, db: Session = Depends(get_db), _u: StaffUser = Depends(get_current_user)):
    r = db.query(Registration).filter(Registration.id == rid).first()
    if not r:
        raise HTTPException(404, "Not found")
    return _reg_to_response(r)

@app.delete("/api/registrations/{rid}")
def delete_registration(rid: str, db: Session = Depends(get_db), _u: StaffUser = Depends(get_current_user)):
    r = db.query(Registration).filter(Registration.id == rid).first()
    if not r:
        raise HTTPException(404, "Not found")
    db.delete(r)
    db.commit()
    return {"message": "Deleted"}

# ─── Auth routes ─────────────────────────────────────────────────────────────

@app.post("/api/auth/login", response_model=TokenResponse)
def login(data: StaffLogin, db: Session = Depends(get_db)):
    user = db.query(StaffUser).filter(StaffUser.username == data.username).first()
    if not user or not verify_password(data.password, user.hashed_password):
        raise HTTPException(401, "Incorrect username or password")
    if not user.is_active:
        raise HTTPException(403, "Account deactivated")
    return TokenResponse(access_token=create_access_token({"sub": user.id}),
        user=StaffResponse(id=user.id, username=user.username, email=user.email,
            is_admin=user.is_admin, is_active=user.is_active))

@app.post("/api/auth/change-password")
def change_password(data: ChangePassword, user: StaffUser = Depends(get_current_user), db: Session = Depends(get_db)):
    if not verify_password(data.current_password, user.hashed_password):
        raise HTTPException(400, "Current password is incorrect")
    user.hashed_password = hash_password(data.new_password)
    db.commit()
    return {"message": "Password changed"}

@app.post("/api/auth/forgot-password")
async def forgot_password(data: ForgotPassword, request: Request, db: Session = Depends(get_db)):
    user = db.query(StaffUser).filter(StaffUser.email == data.email).first()
    if user:
        token = secrets.token_urlsafe(32)
        db.add(PasswordResetToken(user_id=user.id, token=token,
            expires_at=datetime.now(timezone.utc) + timedelta(minutes=settings.PASSWORD_RESET_EXPIRE_MINUTES)))
        db.commit()
        base = settings.FRONTEND_URL or str(request.base_url).rstrip("/")
        await send_password_reset_email(user.email, user.username,
            f"{base}/reset-password?token={token}")
    return {"message": "If the email exists, a reset link has been sent"}

@app.post("/api/auth/reset-password")
def reset_password(data: ResetPasswordSchema, db: Session = Depends(get_db)):
    rt = db.query(PasswordResetToken).filter(
        PasswordResetToken.token == data.token, PasswordResetToken.used == False).first()
    if not rt:
        raise HTTPException(400, "Invalid or expired reset token")
    if rt.expires_at.replace(tzinfo=timezone.utc) < datetime.now(timezone.utc):
        raise HTTPException(400, "Reset token has expired")
    user = db.query(StaffUser).filter(StaffUser.id == rt.user_id).first()
    if not user:
        raise HTTPException(400, "User not found")
    user.hashed_password = hash_password(data.new_password)
    rt.used = True
    db.commit()
    return {"message": "Password reset successfully"}

@app.get("/api/auth/me", response_model=StaffResponse)
def get_me(user: StaffUser = Depends(get_current_user)):
    return StaffResponse(id=user.id, username=user.username, email=user.email,
        is_admin=user.is_admin, is_active=user.is_active)

@app.post("/api/auth/staff", response_model=StaffResponse)
def create_staff(data: StaffCreateSchema, db: Session = Depends(get_db), _a: StaffUser = Depends(get_admin_user)):
    if db.query(StaffUser).filter((StaffUser.username == data.username) | (StaffUser.email == data.email)).first():
        raise HTTPException(400, "Username or email already exists")
    u = StaffUser(username=data.username, email=data.email,
        hashed_password=hash_password(data.password), is_admin=data.is_admin)
    db.add(u)
    db.commit()
    db.refresh(u)
    return StaffResponse(id=u.id, username=u.username, email=u.email, is_admin=u.is_admin, is_active=u.is_active)

@app.get("/api/auth/staff", response_model=list[StaffResponse])
def list_staff(db: Session = Depends(get_db), _a: StaffUser = Depends(get_admin_user)):
    return [StaffResponse(id=u.id, username=u.username, email=u.email, is_admin=u.is_admin, is_active=u.is_active)
        for u in db.query(StaffUser).all()]

@app.delete("/api/auth/staff/{uid}")
def delete_staff(uid: str, db: Session = Depends(get_db), admin: StaffUser = Depends(get_admin_user)):
    if uid == admin.id:
        raise HTTPException(400, "Cannot delete yourself")
    u = db.query(StaffUser).filter(StaffUser.id == uid).first()
    if not u:
        raise HTTPException(404, "Not found")
    db.delete(u)
    db.commit()
    return {"message": "Deleted"}

# ─── Serve frontend static files ────────────────────────────────────────────

_static_dir = Path(__file__).parent / "static"
if _static_dir.is_dir():
    app.mount("/assets", StaticFiles(directory=str(_static_dir / "assets")), name="assets")

    @app.get("/{full_path:path}")
    async def serve_spa(full_path: str):
        file_path = _static_dir / full_path
        if file_path.is_file():
            return FileResponse(str(file_path))
        return FileResponse(str(_static_dir / "index.html"))
