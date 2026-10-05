export interface GuestRegistration {
  id: string;
  room: string;
  lastName: string;
  firstName: string;
  dateOfBirth: string;
  placeOfBirth: string;
  nationality: string;
  occupation: string;
  cinNumber: string;
  moroccoEntryNumber: string;
  arrivalDate: string;
  departureDate: string;
  accompanyingChildren: number;
  comingFrom: string;
  goingTo: string;
  passportNumber: string;
  passportIssueDate: string;
  passportIssuePlace: string;
  permanentAddress: string;
  passportPhoto: string;
  signature: string;
  registrationDate: string;
  createdAt: string;
}

export type Language = 'fr' | 'en' | 'es';
